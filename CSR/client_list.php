<?php
session_start();
include "../db_connect.php";

if (!isset($_SESSION["csr_user"])) {
    http_response_code(401);
    exit("Unauthorized");
}

$search = $_POST["search"] ?? "";

$sql = "
    SELECT 
        u.id,
        u.full_name,
        u.email,
        u.district,
        u.barangay,
        u.assigned_csr,
        (
            SELECT COUNT(*) FROM chat c
            WHERE c.user_id = u.id 
              AND c.sender_type='client' 
              AND c.seen = false
        ) AS unread
    FROM users u
";

if ($search !== "") {
    $sql .= " WHERE LOWER(u.full_name) LIKE LOWER(:search)";
}

$sql .= " ORDER BY unread DESC, full_name ASC";

$stmt = $conn->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%$search%"]);
} else {
    $stmt->execute();
}

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    ?>
    <div class="client-item" data-id="<?= $row['id'] ?>">
        <img src="upload/default-avatar.png" class="client-avatar">
        <div>
            <div class="client-name">
                <?= htmlspecialchars($row["full_name"]) ?>
                <?php if ($row["unread"] > 0): ?>
                    <span class="badge"><?= $row["unread"] ?></span>
                <?php endif ?>
            </div>
            <div class="client-sub">
                <?= htmlspecialchars($row["district"]) ?> | <?= htmlspecialchars($row["barangay"]) ?>
            </div>
        </div>

        <?php if ($row["assigned_csr"] == $_SESSION["csr_user"]): ?>
            <span class="assign-status assigned">Assigned</span>
        <?php elseif (!empty($row["assigned_csr"])): ?>
            <span class="assign-status locked"><i class="fa-solid fa-lock"></i></span>
        <?php endif ?>
    </div>
    <?php
}
?>