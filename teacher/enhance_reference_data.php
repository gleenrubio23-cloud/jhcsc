<?php
// enhance_reference_data.php
include('../includes/db_connect.php');

try {
    // Add more incident types if needed
    $additional_types = [
        ['Academic Dishonesty', 'Cheating, plagiarism, or other academic integrity violations'],
        ['Classroom Disruption', 'Disruptive behavior during class sessions'],
        ['Tardiness', 'Consistent late arrival to classes'],
        ['Equipment Misuse', 'Improper use of school equipment or facilities'],
        ['Bullying', 'Harassment or bullying behavior'],
        ['Dress Code Violation', 'Violation of school dress code'],
        ['Electronic Device Misuse', 'Improper use of phones or electronic devices'],
        ['Unauthorized Access', 'Accessing restricted areas or systems']
    ];

    $insert_type = $pdo->prepare("INSERT IGNORE INTO incident_types (name, description) VALUES (?, ?)");
    foreach ($additional_types as $type) {
        $insert_type->execute($type);
    }

    echo "Reference data enhanced successfully!";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>