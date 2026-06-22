<?php
include_once '../../includes/auth_check.php';

if ($user_role != ROLE_ADMIN) {
    header("Location: " . BASE_URL . "pages/dashboard.php");
    exit;
}

$conn->query("CREATE TABLE IF NOT EXISTS class_fees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    session_id INT NOT NULL,
    term_id INT NULL,
    fee_category_id INT NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX (class_id),
    INDEX (session_id),
    INDEX (term_id),
    INDEX (fee_category_id)
)");

$conn->query("CREATE TABLE IF NOT EXISTS terms (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    term_name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_active TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_term_per_session (session_id, term_name),
    INDEX (session_id),
    INDEX (is_active),
    FOREIGN KEY (session_id) REFERENCES sessions(id)
)");

$classes = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name")->fetch_all(MYSQLI_ASSOC);
$sessions = $conn->query("SELECT id, session_name, is_active FROM sessions ORDER BY is_active DESC, created_at DESC")->fetch_all(MYSQLI_ASSOC);
$terms = $conn->query("SELECT id, term_name, session_id, is_active FROM terms ORDER BY session_id DESC, id ASC")->fetch_all(MYSQLI_ASSOC) ?? [];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fee_name = trim($_POST['fee_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $amount = (float)($_POST['amount'] ?? 0);
    $session_id = (int)($_POST['session_id'] ?? 0);
    $term_id = !empty($_POST['term_id']) ? (int)$_POST['term_id'] : null;
    $class_ids = $_POST['class_ids'] ?? [];

    if ($fee_name == '' || $amount <= 0 || $session_id <= 0) {
        $error = "Fee name, amount, and session are required.";
    } elseif (empty($class_ids)) {
        $error = "Select at least one class this fee applies to.";
    } else {
        $conn->begin_transaction();

        try {
            $stmt = $conn->prepare("SELECT id FROM fee_categories WHERE fee_name = ?");
            $stmt->bind_param("s", $fee_name);
            $stmt->execute();
            $category = $stmt->get_result()->fetch_assoc();

            if ($category) {
                $fee_category_id = (int)$category['id'];
                $stmt = $conn->prepare("UPDATE fee_categories SET description = ? WHERE id = ?");
                $stmt->bind_param("si", $description, $fee_category_id);
                $stmt->execute();
            } else {
                $stmt = $conn->prepare("INSERT INTO fee_categories (fee_name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $fee_name, $description);
                $stmt->execute();
                $fee_category_id = $conn->insert_id;
            }

            $class_count = 0;
            $student_fee_count = 0;

            foreach ($class_ids as $class_id) {
                $class_id = (int)$class_id;
                if ($class_id <= 0) {
                    continue;
                }

                if ($term_id === null) {
                    $stmt = $conn->prepare("SELECT id FROM class_fees WHERE class_id = ? AND session_id = ? AND term_id IS NULL AND fee_category_id = ?");
                    $stmt->bind_param("iii", $class_id, $session_id, $fee_category_id);
                } else {
                    $stmt = $conn->prepare("SELECT id FROM class_fees WHERE class_id = ? AND session_id = ? AND term_id = ? AND fee_category_id = ?");
                    $stmt->bind_param("iiii", $class_id, $session_id, $term_id, $fee_category_id);
                }
                $stmt->execute();
                $existing_class_fee = $stmt->get_result()->fetch_assoc();

                if ($existing_class_fee) {
                    $stmt = $conn->prepare("UPDATE class_fees SET amount = ? WHERE id = ?");
                    $stmt->bind_param("di", $amount, $existing_class_fee['id']);
                    $stmt->execute();
                } else {
                    $stmt = $conn->prepare("INSERT INTO class_fees (class_id, session_id, term_id, fee_category_id, amount) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("iiiid", $class_id, $session_id, $term_id, $fee_category_id, $amount);
                    $stmt->execute();
                }

                $class_count++;
                $students_stmt = $conn->prepare("SELECT id FROM students WHERE class_id = ? AND is_active = 1");
                $students_stmt->bind_param("i", $class_id);
                $students_stmt->execute();
                $students = $students_stmt->get_result();

                while ($student = $students->fetch_assoc()) {
                    $student_id = (int)$student['id'];

                    if ($term_id === null) {
                        $stmt = $conn->prepare("SELECT id FROM student_fees WHERE student_id = ? AND session_id = ? AND term_id IS NULL AND fee_category_id = ?");
                        $stmt->bind_param("iii", $student_id, $session_id, $fee_category_id);
                    } else {
                        $stmt = $conn->prepare("SELECT id FROM student_fees WHERE student_id = ? AND session_id = ? AND term_id = ? AND fee_category_id = ?");
                        $stmt->bind_param("iiii", $student_id, $session_id, $term_id, $fee_category_id);
                    }
                    $stmt->execute();
                    $existing_student_fee = $stmt->get_result()->fetch_assoc();

                    if ($existing_student_fee) {
                        $stmt = $conn->prepare("UPDATE student_fees SET amount = ? WHERE id = ?");
                        $stmt->bind_param("di", $amount, $existing_student_fee['id']);
                        $stmt->execute();
                    } else {
                        $stmt = $conn->prepare("INSERT INTO student_fees (student_id, session_id, term_id, fee_category_id, amount, is_paid) VALUES (?, ?, ?, ?, ?, 0)");
                        $stmt->bind_param("iiiid", $student_id, $session_id, $term_id, $fee_category_id, $amount);
                        $stmt->execute();
                        $student_fee_count++;
                    }
                }
            }

            $conn->commit();
            $success = "Fee saved for $class_count class(es). Added $student_fee_count student fee record(s).";
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error saving fee: " . $e->getMessage();
        }
    }
}

$assigned_fees = $conn->query("SELECT class_fees.amount, class_fees.created_at, classes.class_name, sessions.session_name, terms.term_name, fee_categories.fee_name
    FROM class_fees
    INNER JOIN classes ON class_fees.class_id = classes.id
    INNER JOIN sessions ON class_fees.session_id = sessions.id
    LEFT JOIN terms ON class_fees.term_id = terms.id
    INNER JOIN fee_categories ON class_fees.fee_category_id = fee_categories.id
    ORDER BY class_fees.created_at DESC, fee_categories.fee_name, classes.class_name
    LIMIT 100");

// Recent payments (for admin overview)
$recent_payments = $conn->query(
    "SELECT p.id, p.student_id, p.amount_paid,
        COALESCE(p.payment_reference, '') AS reference,
        COALESCE(p.payment_method, '') AS payment_method,
        COALESCE(p.created_at, p.payment_date, '') AS created_at,
        s.first_name, s.last_name, s.admission_no, c.class_name
    FROM payments p
    LEFT JOIN students s ON p.student_id = s.id
    LEFT JOIN classes c ON s.class_id = c.id
    ORDER BY COALESCE(p.created_at, p.payment_date) DESC
    LIMIT 200"
)->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fees Management - SPMS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <?php include_once '../../includes/navbar.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Fees Management</h1>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-1 bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold mb-4">Create Class Fee</h2>

                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block font-bold mb-2">Fee Name *</label>
                        <input type="text" name="fee_name" required placeholder="Tuition Fee" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Amount *</label>
                        <input type="number" name="amount" required min="1" step="0.01" class="w-full border border-gray-300 rounded px-3 py-2">
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Session *</label>
                        <select name="session_id" required class="w-full border border-gray-300 rounded px-3 py-2">
                            <option value="">Select Session</option>
                            <?php foreach ($sessions as $session): ?>
                                <option value="<?php echo $session['id']; ?>">
                                    <?php echo htmlspecialchars($session['session_name']); ?><?php echo $session['is_active'] ? ' (Active)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Term</label>
                        <select name="term_id" class="w-full border border-gray-300 rounded px-3 py-2">
                            <option value="">All / No Term</option>
                            <?php foreach ($terms as $term): ?>
                                <option value="<?php echo $term['id']; ?>">
                                    <?php echo htmlspecialchars($term['term_name']); ?><?php echo $term['is_active'] ? ' (Active)' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold mb-2">Description</label>
                        <textarea name="description" rows="3" class="w-full border border-gray-300 rounded px-3 py-2"></textarea>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label class="block font-bold">Applies To Classes *</label>
                            <label class="inline-flex items-center gap-2 text-sm">
                                <input type="checkbox" id="checkAllClasses" class="rounded">
                                Check all
                            </label>
                        </div>

                        <div class="border border-gray-300 rounded p-3 max-h-56 overflow-y-auto space-y-2">
                            <?php if (!empty($classes)): ?>
                                <?php foreach ($classes as $class): ?>
                                    <label class="flex items-center gap-2">
                                        <input type="checkbox" name="class_ids[]" value="<?php echo $class['id']; ?>" class="class-checkbox rounded">
                                        <span><?php echo htmlspecialchars($class['class_name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <p class="text-gray-600 text-sm">No classes have been created yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded hover:bg-indigo-700 font-semibold">
                        Save Fee
                    </button>
                </form>
            </div>

            <div class="lg:col-span-2 bg-white rounded-lg shadow overflow-hidden">
                <div class="border-b flex">
                    <button type="button" id="tabClassFeesBtn" class="fee-tab-btn px-6 py-4 font-semibold text-indigo-600 border-b-2 border-indigo-600">
                        Class Fees
                    </button>
                    <button type="button" id="tabPaymentsBtn" class="fee-tab-btn px-6 py-4 font-semibold text-gray-500 border-b-2 border-transparent hover:text-gray-700">
                        Recent Payments
                        <?php if (!empty($recent_payments)): ?>
                            <span class="ml-1 bg-gray-200 text-gray-700 text-xs font-bold px-2 py-0.5 rounded-full"><?php echo count($recent_payments); ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <div id="tabClassFees" class="fee-tab-panel">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-100">
                                <tr>
                                    <th class="text-left px-6 py-3">Fee</th>
                                    <th class="text-left px-6 py-3">Class</th>
                                    <th class="text-left px-6 py-3">Session</th>
                                    <th class="text-left px-6 py-3">Term</th>
                                    <th class="text-left px-6 py-3">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($assigned_fees && $assigned_fees->num_rows > 0): ?>
                                    <?php while ($fee = $assigned_fees->fetch_assoc()): ?>
                                        <tr class="border-t">
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($fee['fee_name']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($fee['class_name']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($fee['session_name']); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($fee['term_name'] ?? '-'); ?></td>
                                            <td class="px-6 py-4">₦<?php echo number_format($fee['amount'], 2); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="px-6 py-8 text-center text-gray-600">No class fees created yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div id="tabPayments" class="fee-tab-panel hidden">
                    <?php if (empty($recent_payments)): ?>
                        <div class="px-6 py-10 text-sm text-gray-600 text-center">No payments recorded yet.</div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-gray-100">
                                    <tr>
                                        <th class="text-left px-6 py-3">Student</th>
                                        <th class="text-left px-6 py-3">Admission No</th>
                                        <th class="text-left px-6 py-3">Class</th>
                                        <th class="text-left px-6 py-3">Amount</th>
                                        <th class="text-left px-6 py-3">Method</th>
                                        <th class="text-left px-6 py-3">Reference</th>
                                        <th class="text-left px-6 py-3">Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_payments as $p): ?>
                                        <tr class="border-t">
                                            <td class="px-6 py-4"><?php echo htmlspecialchars(($p['first_name'] || $p['last_name']) ? trim($p['first_name'].' '.$p['last_name']) : '—'); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($p['admission_no'] ?? '-'); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($p['class_name'] ?? '-'); ?></td>
                                            <td class="px-6 py-4">₦<?php echo number_format($p['amount_paid'], 2); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($p['payment_method'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($p['reference'] ?? ''); ?></td>
                                            <td class="px-6 py-4"><?php echo htmlspecialchars($p['created_at'] ?? ''); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Terms data organized by session
        const termsData = <?php echo json_encode(array_reduce($terms, function($acc, $term) {
            $session_id = $term['session_id'];
            if (!isset($acc[$session_id])) {
                $acc[$session_id] = [];
            }
            $acc[$session_id][] = $term;
            return $acc;
        }, [])); ?>;

        const sessionSelect = document.querySelector('select[name="session_id"]');
        const termSelect = document.querySelector('select[name="term_id"]');
        const checkAllClasses = document.getElementById('checkAllClasses');
        const classCheckboxes = Array.from(document.querySelectorAll('.class-checkbox'));

        // Function to update terms dropdown based on selected session
        function updateTermsDropdown() {
            const selectedSessionId = sessionSelect.value;
            const currentTermId = termSelect.value;
            
            // Remove all term options except the first one
            const firstOption = termSelect.querySelector('option:first-child');
            termSelect.innerHTML = '';
            termSelect.appendChild(firstOption);
            
            // Add terms for the selected session
            if (selectedSessionId && termsData[selectedSessionId]) {
                termsData[selectedSessionId].forEach(term => {
                    const option = document.createElement('option');
                    option.value = term.id;
                    option.textContent = term.term_name + (term.is_active ? ' (Active)' : '');
                    termSelect.appendChild(option);
                });
                
                // Restore previous selection if it exists
                if (currentTermId && termSelect.querySelector(`option[value="${currentTermId}"]`)) {
                    termSelect.value = currentTermId;
                }
            }
        }

        // Listen for session selection changes
        sessionSelect.addEventListener('change', updateTermsDropdown);

        // Initialize terms on page load
        document.addEventListener('DOMContentLoaded', () => {
            if (sessionSelect.value) {
                updateTermsDropdown();
            }
        });

        // Check/uncheck all classes functionality
        checkAllClasses.addEventListener('change', () => {
            classCheckboxes.forEach((checkbox) => {
                checkbox.checked = checkAllClasses.checked;
            });
        });

        classCheckboxes.forEach((checkbox) => {
            checkbox.addEventListener('change', () => {
                checkAllClasses.checked = classCheckboxes.length > 0 && classCheckboxes.every((item) => item.checked);
            });
        });

        // Tab switching: Class Fees <-> Recent Payments
        const tabClassFeesBtn = document.getElementById('tabClassFeesBtn');
        const tabPaymentsBtn = document.getElementById('tabPaymentsBtn');
        const tabClassFees = document.getElementById('tabClassFees');
        const tabPayments = document.getElementById('tabPayments');

        const activeClasses = ['text-indigo-600', 'border-indigo-600'];
        const inactiveClasses = ['text-gray-500', 'border-transparent'];

        function showTab(tab) {
            const isFees = tab === 'fees';

            tabClassFees.classList.toggle('hidden', !isFees);
            tabPayments.classList.toggle('hidden', isFees);

            tabClassFeesBtn.classList.toggle('text-indigo-600', isFees);
            tabClassFeesBtn.classList.toggle('border-indigo-600', isFees);
            tabClassFeesBtn.classList.toggle('text-gray-500', !isFees);
            tabClassFeesBtn.classList.toggle('border-transparent', !isFees);

            tabPaymentsBtn.classList.toggle('text-indigo-600', !isFees);
            tabPaymentsBtn.classList.toggle('border-indigo-600', !isFees);
            tabPaymentsBtn.classList.toggle('text-gray-500', isFees);
            tabPaymentsBtn.classList.toggle('border-transparent', isFees);
        }

        tabClassFeesBtn?.addEventListener('click', () => showTab('fees'));
        tabPaymentsBtn?.addEventListener('click', () => showTab('payments'));
    </script>
</body>
</html>