<?php

namespace App\Models;

use App\Models\Concerns\DescribesMaterial;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * 資材マスタモデル。
 */
#[Fillable([
    'name', 'category_id', 'supplier_id',
    'length_mm', 'width_mm', 'height_mm', 'shipping_size', 'size_text',
    'unit_id', 'unit_price', 'min_lot_qty', 'has_imprint', 'note', 'is_active', 'image_path',
])]
class Material extends Model
{
    use DescribesMaterial;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'has_imprint' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 資材1件の入力チェック。編集フォーム（Admin\MaterialController）と
     * CSV取り込み（App\Support\MaterialCsv）で同じものを使う。
     */
    public static function validationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'exists:categories,id'],
            'supplier_id' => ['nullable', 'exists:suppliers,id'],
            'length_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'width_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            'height_mm' => ['nullable', 'integer', 'min:0', 'max:99999'],
            // 実際に運送会社で測られるサイズ（「60サイズ」など）。運用で分かる値なので手入力
            'shipping_size' => ['nullable', 'string', 'max:10'],
            'size_text' => ['nullable', 'string', 'max:100'],
            'unit_id' => ['required', 'exists:units,id'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'min_lot_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'note' => ['nullable', 'string', 'max:1000'],
            'has_imprint' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }

    /** エラーメッセージに出す項目名（日本語） */
    public static function attributeNames(): array
    {
        return [
            'name' => '品名',
            'category_id' => '商品カテゴリ',
            'supplier_id' => '発注業者',
            // W＝幅長 / D＝奥行巾 / H＝高さ。列名と文字の対応が違うので注意
            'length_mm' => 'D（奥行巾）',
            'width_mm' => 'W（幅長）',
            'height_mm' => 'H（高さ）',
            'shipping_size' => '発送時サイズ',
            'size_text' => 'サイズ',
            'unit_id' => '単位',
            'unit_price' => '単価',
            'min_lot_qty' => '最低ロット数量',
            'note' => '備考',
            'has_imprint' => '名入れ',
            'is_active' => '有効',
        ];
    }

    /**
     * 資材の一覧はどこでも「カテゴリ順 → 品名順」で並べる。
     * categories を join するので、この後に条件を足すときは
     * is_active のような同名カラムをテーブル名で修飾すること。
     */
    public function scopeSortedByCategory(Builder $query): Builder
    {
        return $query
            ->leftJoin('categories', 'materials.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.name')
            ->orderBy('materials.name')
            ->select('materials.*');
    }

    /** 発注できる資材（有効なもの） */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('materials.is_active', true);
    }

    /** 商品カテゴリ */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /** 仕入先業者 */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /** 単位 */
    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    /** 画像の公開URL（未登録なら null） */
    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * この資材を発注明細にするときのスナップショット。
     *
     * 明細は「申請時点の資材の姿」を焼き付けて保存する（マスタが後で変わっても
     * 過去の申請・集計・発注書が動かないように）。組み立てが2箇所に散らないよう、
     * 資材側で1つにまとめている。呼び出し側は数量を足すだけでよい。
     */
    public function toOrderItemSnapshot(): array
    {
        return [
            'material_id' => $this->id,
            'material_name' => $this->name,
            'category_id' => $this->category_id,
            'category_name' => $this->category?->name,
            'supplier_id' => $this->supplier_id,
            'supplier_name' => $this->supplier?->name,
            'unit' => $this->unit?->name,
            'unit_price' => $this->unit_price,
            'length_mm' => $this->length_mm,
            'width_mm' => $this->width_mm,
            'height_mm' => $this->height_mm,
            'size_text' => $this->size_text,
            'min_lot_qty' => $this->min_lot_qty,
        ];
    }
}
