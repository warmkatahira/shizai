<?php

namespace App\Models;

use App\Models\Concerns\DescribesMaterial;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 資材マスタモデル。
 */
#[Fillable([
    'name', 'category_id', 'supplier_id',
    'length_mm', 'width_mm', 'height_mm',
    'unit', 'unit_price', 'min_lot_qty', 'min_lot_unit', 'has_imprint', 'note', 'is_active',
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
            'unit' => ['required', 'string', 'max:20'],
            'unit_price' => ['nullable', 'numeric', 'min:0', 'max:99999999'],
            'min_lot_qty' => ['nullable', 'integer', 'min:0', 'max:9999999'],
            'min_lot_unit' => ['nullable', 'string', 'max:20'],
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
            'length_mm' => '縦',
            'width_mm' => '横',
            'height_mm' => '高さ',
            'unit' => '単位',
            'unit_price' => '単価',
            'min_lot_qty' => '最低ロット数量',
            'min_lot_unit' => '最低ロットの単位',
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
            'unit' => $this->unit,
            'unit_price' => $this->unit_price,
            'length_mm' => $this->length_mm,
            'width_mm' => $this->width_mm,
            'height_mm' => $this->height_mm,
            'min_lot_qty' => $this->min_lot_qty,
            'min_lot_unit' => $this->min_lot_unit,
        ];
    }
}
