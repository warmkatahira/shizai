<?php

/**
 * バリデーションの日本語メッセージ。
 *
 * Laravel標準は英語（"The 品名 field is required."）で、画面にもCSV取り込みの
 * エラー一覧にもそのまま出てしまうため、この中で使っている規則ぶんだけ日本語にしている。
 * 項目名（:attribute）は各コントローラー／モデル側で日本語を渡している
 * （例：Material::attributeNames()）。
 */
return [
    'required' => ':attribute は必ず入力してください。',
    'string' => ':attribute は文字で入力してください。',
    'boolean' => ':attribute の指定が正しくありません。',
    'integer' => ':attribute は整数で入力してください。',
    'numeric' => ':attribute は数値で入力してください。',
    'array' => ':attribute の形式が正しくありません。',
    'date' => ':attribute は日付で入力してください。',
    'after' => ':attribute は :date より後の日付にしてください。',
    'before' => ':attribute は :date より前の日付にしてください。',
    'email' => ':attribute はメールアドレスの形式で入力してください。',
    'exists' => '選択された :attribute は登録されていません。',
    'unique' => 'その :attribute はすでに使われています。',
    'confirmed' => ':attribute の確認が一致しません。',
    'in' => '選択された :attribute は正しくありません。',
    'file' => ':attribute はファイルを選択してください。',
    'mimes' => ':attribute は :values 形式のファイルにしてください。',

    'min' => [
        'numeric' => ':attribute は :min 以上にしてください。',
        'string' => ':attribute は :min 文字以上で入力してください。',
        'file' => ':attribute は :min KB 以上にしてください。',
        'array' => ':attribute は :min 個以上にしてください。',
    ],
    'max' => [
        'numeric' => ':attribute は :max 以下にしてください。',
        'string' => ':attribute は :max 文字以内で入力してください。',
        'file' => ':attribute は :max KB 以内にしてください。',
        'array' => ':attribute は :max 個以内にしてください。',
    ],

    'attributes' => [],
];
