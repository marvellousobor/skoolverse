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

    $colCheck = $conn->query("SHOW COLUMNS FROM subjects LIKE 'subject_code'");
    if ($colCheck && $colCheck->num_rows === 0) {
        $conn->query("ALTER TABLE subjects ADD COLUMN subject_code VARCHAR(20) AFTER subject_name");
    }

    $conn->query("CREATE TABLE IF NOT EXISTS class_subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        class_id INT NOT NULL,
        subject_id INT NOT NULL,
        is_compulsory TINYINT(1) DEFAULT 1,
        UNIQUE KEY unique_class_subject (class_id, subject_id),
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )");

    $conn->query("CREATE TABLE IF NOT EXISTS student_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        class_id INT NOT NULL,
        session_id INT NOT NULL,
        term_id INT NOT NULL,
        subject_id INT NOT NULL,
        score DECIMAL(5,2) NOT NULL,
        ca_score DECIMAL(5,2) NULL,
        exam_score DECIMAL(5,2) NULL,
        uploaded_by_teacher_id INT NULL,
        published_by_admin_id INT NULL,
        is_published TINYINT(1) NOT NULL DEFAULT 0,
        published_at TIMESTAMP NULL DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_result (student_id, class_id, session_id, term_id, subject_id),
        INDEX (student_id),
        INDEX (class_id),
        INDEX (session_id),
        INDEX (term_id),
        INDEX (subject_id),
        INDEX (is_published),
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (class_id) REFERENCES classes(id),
        FOREIGN KEY (session_id) REFERENCES sessions(id),
        FOREIGN KEY (term_id) REFERENCES terms(id),
        FOREIGN KEY (subject_id) REFERENCES subjects(id)
    )");

    $resultColumns = [
        'ca_score' => "ALTER TABLE student_results ADD COLUMN ca_score DECIMAL(5,2) NULL AFTER score",
        'exam_score' => "ALTER TABLE student_results ADD COLUMN exam_score DECIMAL(5,2) NULL AFTER ca_score",
        'uploaded_by_teacher_id' => "ALTER TABLE student_results ADD COLUMN uploaded_by_teacher_id INT NULL AFTER exam_score",
        'published_by_admin_id' => "ALTER TABLE student_results ADD COLUMN published_by_admin_id INT NULL AFTER uploaded_by_teacher_id",
        'is_published' => "ALTER TABLE student_results ADD COLUMN is_published TINYINT(1) NOT NULL DEFAULT 0 AFTER published_by_admin_id",
        'published_at' => "ALTER TABLE student_results ADD COLUMN published_at TIMESTAMP NULL DEFAULT NULL AFTER is_published",
    ];

    foreach ($resultColumns as $columnName => $sql) {
        $column = $conn->query("SHOW COLUMNS FROM student_results LIKE '$columnName'");
        if ($column && $column->num_rows === 0) {
            $conn->query($sql);
        }
    }

    $conn->query("CREATE TABLE IF NOT EXISTS grading_scales (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade VARCHAR(5) NOT NULL,
        lower_bound DECIMAL(5,2) NOT NULL,
        upper_bound DECIMAL(5,2) NOT NULL,
        remark VARCHAR(100) NOT NULL DEFAULT '',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");

    $check = $conn->query("SELECT COUNT(*) as cnt FROM grading_scales");
    if ($check && $check->fetch_assoc()['cnt'] == 0) {
        $conn->query("INSERT INTO grading_scales (grade, lower_bound, upper_bound, remark) VALUES
            ('A1', 75, 100, 'Excellent'),
            ('B2', 70, 74.99, 'Very Good'),
            ('B3', 65, 69.99, 'Good'),
            ('C4', 60, 64.99, 'Credit'),
            ('C5', 55, 59.99, 'Credit'),
            ('C6', 50, 54.99, 'Credit'),
            ('D7', 45, 49.99, 'Pass'),
            ('E8', 40, 44.99, 'Pass'),
            ('F9', 0, 39.99, 'Fail')");
    }
}
?>
