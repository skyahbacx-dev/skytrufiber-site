<?php
session_start();
include "../db_connect.php";

$csrUser = $_SESSION["csr_user"] ?? null;

if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

/*
   Retrieve clients + unread message count
   Unread = client messages sent AFTER CSR last read time
*/
$sql = "
    SELECT c.id, c.name, c.assigned_csr,
    (
        SELECT COUNT(*) FROM chat m
        WHERE m.client_id = c.id
        AND m.sender_type = 'client'
        AND m.seen = 0
    ) AS unread
    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY c.name ASC";

$stmt = $conn->prepare($sql);
$params = [];

if ($search !== "") $params[":search"] = "%$search%";

$stmt->execute($params);

/*
   Render UI
*/
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    $avatar = "upload/default-avatar.png";
?>
    <div class="client-item" id="client-<?php echo $id; ?>" onclick="selectClient(<?php echo $id; ?>, '<?php echo $name; ?>', '<?php echo $assigned; ?>')">

        <img src="<?php echo $avatar; ?>" class="client-avatar">

        <div class="client-content">
            <div class="client-name">
                <?php echo $name; ?>
                <?php if ($unread > 0) { ?>
                    <span class="badge"><?php echo $unread; ?></span>
                <?php } ?>
            </div>

            <div class="client-sub">
                <?php
                    if ($assigned === null || $assigned === "") {
                        echo "Unassigned";
                    } elseif ($assigned === $csrUser) {
                        echo "Assigned to YOU";
                    } else {
                        echo "Assigned to $assigned";
                    }
                ?>
            </div>
        </div>

        <div class="client-actions">
            <?php if ($assigned === null || $assigned === "") { ?>

                <button class="pill green" onclick="event.stopPropagation(); showAssignPopup(<?php echo $id; ?>)">
                    ➕
                </button>

            <?php } elseif ($assigned === $csrUser) { ?>

                <button class="pill red" onclick="event.stopPropagation(); showUnassignPopup(<?php echo $id; ?>)">
                    ➖
                </button>

            <?php } else { ?>

                <button class="pill gray" disabled title="Handled by <?php echo $assigned; ?>">
                    🔒
                </button>

            <?php } ?>
        </div>
    </div>
<?php
}
?>
