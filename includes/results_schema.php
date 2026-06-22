<?php
function ensure_results_schema(mysqli $conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        subject_name VARCHAR(100) NOT NULL UNIQUE,
        subject_code VARCHAR(20),
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $column = $conn->query("SHOW COLUMNS FROM subjects LIKE 'is_active'");
    if ($column && $column->num_rows === 0) {
        $conn->query("ALTER TABLE subjects ADD COLUMN is_active TINYINT(1) DEFAULT 1");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS student_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        session_id INT NOT NULL,
        term_id INT NOT NULL,
        subject_id INT NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_result (student_id, class_id, session_id, term_id, subject_id),
        INDEX (student_id),
        INDEX (class_id),
        INDEX (session_id),
        INDEX (term_id),
        INDEX (subject_id),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (session_id) REFERENCES sessions(id),
        FOREIGN KEY (term_id) REFERENCES terms(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )");
}
?>
