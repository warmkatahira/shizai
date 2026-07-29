<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * マスタ管理画面のCSV出力・取り込みの入口。
 * 実際の中身（列・変換・検証）は App\Support\MasterCsv の継承先が持つ。
 */
trait HandlesMasterCsv
{
    /**
     * CSVをダウンロードさせる。ExcelでUTF-8を正しく開けるようBOMを付ける。
     *
     * @param  class-string<\App\Support\MasterCsv>  $csvClass
     * @param  iterable<\Illuminate\Database\Eloquent\Model>  $records
     */
    protected function streamCsv(string $csvClass, iterable $records, string $filenamePrefix): StreamedResponse
    {
        $filename = $filenamePrefix . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($csvClass, $records) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $csvClass::headers());

            foreach ($records as $record) {
                fputcsv($out, $csvClass::row($record));
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * アップロードされたCSVを取り込み、件数つきのメッセージで一覧へ戻す。
     * 取り込みに失敗した場合は ValidationException が投げられ、
     * 行番号つきのエラーが一覧画面に出る（何も取り込まれない）。
     *
     * @param  class-string<\App\Support\MasterCsv>  $csvClass
     */
    protected function importCsv(Request $request, string $csvClass, string $redirectTo, string $label): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:5120'],
        ], [], ['file' => 'CSVファイル']);

        $result = $csvClass::import($request->file('file')->getRealPath());

        return redirect($redirectTo)->with(
            'status',
            "{$label}のCSVを取り込みました（新規 {$result['created']} 件 / 更新 {$result['updated']} 件）。",
        );
    }
}
