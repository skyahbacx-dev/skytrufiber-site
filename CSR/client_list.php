<?php
session_start();
include "../db_connect.php";
header("Content-Type: text/html; charset=utf-8");

$csrUser = $_SESSION["csr_user"] ?? null;
if (!$csrUser) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_GET["search"] ?? "";

/*
  FIXED unread counter (PostgreSQL)
  boolean => use false instead of 0
  MAX(last_read) avoids cardinality violation
*/
$sql = "
    SELECT
        c.id,
        c.name,
        c.assigned_csr,

        (
            SELECT COUNT(*) FROM chat m
            WHERE m.client_id = c.id
            AND m.sender_type = 'client'
            AND m.seen = false
            AND m.created_at > COALESCE(
                (SELECT MAX(last_read)
                 FROM chat_read r
                 WHERE r.client_id = c.id
                 AND r.csr = :csr),
                '2000-01-01'
            )
        ) AS unread

    FROM clients c
";

if ($search !== "") {
    $sql .= " WHERE LOWER(c.name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, c.name ASC";

$stmt = $conn->prepare($sql);
$params = [":csr" => $csrUser];
if ($search !== "") $params[":search"] = "%$search%";
$stmt->execute($params);

$avatar = "upload/default-avatar.png";

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    $id       = $row["id"];
    $name     = htmlspecialchars($row["name"]);
    $assigned = $row["assigned_csr"];
    $unread   = intval($row["unread"]);

    // Determine icon state
    $btn = "";

    if ($assigned === null) {
        // not assigned at all → plus (assign)
        $btn = "<button class='assign-btn' onclick='event.stopPropagation(); showAssignPopup($id)'>
                    <i class=\"fa-solid fa-plus\"></i>
                </button>";
    } elseif ($assigned === $csrUser) {
        // assigned to you → minus (unassign)
        $btn = "<button class='unassign-btn' onclick='event.stopPropagation(); showUnassignPopup($id)'>
                    <i class=\"fa-solid fa-minus\"></i>
                </button>";
    } else {
        // assigned to someone else → locked
        $btn = "<button class='locked-btn' disabled>
                    <i class=\"fa-solid fa-lock\"></i>
                </button>";
    }

    echo "
    <div class='client-item' id='client-$id' onclick='selectClient($id, \"$name\", \"$assigned\")'>
        <img src='$avatar' class='client-avatar'>

        <div class='client-content'>
            <div class='client-name'>
                $name " . ($unread > 0 ? "<span class='badge'>$unread</span>" : "") . "
            </div>

            <div class='client-sub'>
                " . ($assigned === null ? "Unassigned"
                    : ($assigned === $csrUser ? "Assigned to YOU"
                    : "Assigned to $assigned")) . "
            </div>
        </div>

        <div class='client-action'>
            $btn
        </div>

    </div>";
}
?>
