<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DocxToPdfController extends Controller
{
    public function convert(Request $request)
    {
        $request->validate([
            'docx' => 'required|mimes:docx|max:30720' // до 30 МБ
        ]);

        $docxFile = $request->file('docx');
        $originalName = pathinfo($docxFile->getClientOriginalName(), PATHINFO_FILENAME);
        $random = Str::random(10);

        // Папки
        $inputDir  = storage_path('app/converter/input');
        $outputDir = storage_path('app/converter/output');

        $inputPath  = $inputDir . '/' . $random . '_' . $docxFile->getClientOriginalName();
        $outputPath = $outputDir . '/' . $originalName . '_' . $random . '.pdf';

        // Копируем загруженный файл
        $docxFile->move($inputDir, basename($inputPath));

        // Команда для Linux/macOS
        $command = [
            'libreoffice',
            '--headless',
            '--convert-to', 'pdf',
            '--outdir', $outputDir,
            $inputPath
        ];

        $process = new Process($command);
        $process->setTimeout(120); // 2 минуты максимум
        $process->run();

        // Удаляем исходник
        @unlink($inputPath);

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        if (!file_exists($outputPath)) {
            return response()->json(['error' => 'PDF не создан'], 500);
        }

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }
}
