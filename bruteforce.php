<?php
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

require 'vendor/autoload.php'; // تضمين مكتبات Composer

$request = Request::createFromGlobals();

if ($request->isMethod('POST')) {
    $username = $request->request->get('username');
    $passwordFile = $request->files->get('passwordFile');

    if (!empty($username) && $passwordFile !== null) {
        // تحقق من وجود مجلد لحفظ النتائج
        if (!file_exists('results')) {
            mkdir('results', 0777, true);
        }

        // استخراج معلومات الملف
        $passwordFileTmpName = $passwordFile->getRealPath();

        // تحقق من نوع الملف (يجب أن يكون نصيًا)
        if ($passwordFile->getClientMimeType() !== 'text/plain') {
            echo "نوع الملف غير صالح. يجب أن يكون الملف نصيًا.";
            exit;
        }

        // افتح الملف وقم بتخمين كلمات المرور
        $file = fopen($passwordFileTmpName, 'r');
        $totalPasswords = 14344087; // عدد كلمات المرور في ملف rockyou.txt

        // تحديد عدد الأسطر لكل برنامج فرعي
        $linesPerWorker = 500;
        $numWorkers = ceil($totalPasswords / $linesPerWorker);

        // إنشاء مصفوفة لتخزين الأعمال الفرعية
        $workers = [];

        // توزيع العمل على البرامج الفرعية
        for ($i = 0; $i < $numWorkers; $i++) {
            $startLine = $i * $linesPerWorker;
            $endLine = ($i + 1) * $linesPerWorker - 1;
            $workers[] = [
                'startLine' => $startLine,
                'endLine' => $endLine,
            ];
        }

        // قم بتنفيذ البرامج الفرعية بالتوازي
        foreach ($workers as $worker) {
            $pid = pcntl_fork();
            if ($pid == -1) {
                die('فشل في إنشاء برنامج فرعي.');
            } elseif ($pid == 0) {
                // هذا هو البرنامج الفرعي
                $startLine = $worker['startLine'];
                $endLine = $worker['endLine'];

                for ($lineNumber = $startLine; $lineNumber <= $endLine; $lineNumber++) {
                    fseek($file, $lineNumber);
                    $line = trim(fgets($file));

                    // اذا تم العثور على كلمة المرور الصحيحة
                    if (checkPassword($username, $line)) {
                        // إرسال الكلمة المرور الصحيحة إلى البرنامج الرئيسي
                        echo "Password found: $line\n";
                        file_put_contents('results/password.txt', $line, FILE_APPEND);
                        exit(0);
                    }
                }

                // إنهاء البرنامج الفرعي بنجاح
                exit(0);
            }
        }

        // انتظر حتى انتهاء جميع البرامج الفرعية
        pcntl_wait($status);

        // إغلاق الملف بعد الانتهاء من البحث
        fclose($file);
    } else {
        echo "يرجى ملء جميع الحقول المطلوبة.";
    }
} else {
    echo "الصفحة يجب أن تتم بواسطة POST request.";
}

function checkPassword($username, $password) {
    // قم بإجراء الاختبار هنا واعمل على تحسين أدائه
    // يمكنك استخدام تقنيات تخمين أكثر ذكاءً هنا
    // لمجرد الاختبار، سأستخدم تحقق بسيط من اسم المستخدم وكلمة المرور
    if ($username === 'testuser' && $password === 'testpass') {
        return true;
    }

    return false;
}
?>
