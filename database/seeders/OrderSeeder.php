<?php

namespace Database\Seeders;

use App\Models\Material;
use App\Models\Office;
use App\Models\Order;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * 動作確認用の発注申請データ（10件）。
 *
 * 実際の申請と同じルールで作る：
 * - 1申請＝1業者。明細はその業者の資材だけ
 * - 数量は最低ロットの倍数
 * - 明細は申請時点の資材情報をスナップショット保存
 *
 * ステータスは所長承認待ち・総務承認待ち・発注済・却下を混ぜてあり、
 * 発注済は当月と先月に散らしてあるので集計画面の動作も確認できる。
 *
 * OfficeSeeder / UserSeeder / MaterialSeeder より後に実行すること。
 */
class OrderSeeder extends Seeder
{
    /**
     * [営業所コード, 業者名, 発注者の氏名, ステータス, 何日前か(発注済/却下のみ), 業者への連絡事項]
     * 明細はその業者の資材から自動で選ぶ。
     */
    private const ORDERS = [
        ['1st', 'セッツカートン', '里本 佳隆', Order::STATUS_ORDERED, 40, '送状備考欄に「ラッド分」と明記お願いします。'],
        ['3rd', 'イクソブ', '杉本 健一', Order::STATUS_ORDERED, 35, null],
        ['1st', 'フレックス', '山田 太郎', Order::STATUS_ORDERED, 12, '分納可。'],
        ['2nd', 'アイセカンド', '曽根田 裕也', Order::STATUS_ORDERED, 9, null],
        ['LS', '共立', '大森 幸子', Order::STATUS_ORDERED, 6, '名入れの版は前回と同じもので。'],
        ['LP', 'フレックス', '金子 直樹', Order::STATUS_ORDERED, 3, null],
        ['HR', 'セッツカートン', '中村 亮', Order::STATUS_PENDING_AFFAIRS, null, null],
        ['LP', 'アイセカンド', '金子 直樹', Order::STATUS_PENDING_ORDER, null, null],
        ['2nd', 'セッツカートン', '佐藤 美咲', Order::STATUS_PENDING_MANAGER, null, null],
        ['IMP', 'アイセカンド', '高橋 一郎', Order::STATUS_PENDING_MANAGER, null, '午前中の納品でお願いします。'],
        ['LC', 'フレックス', '小林 慎', Order::STATUS_REJECTED, 5, null],
    ];

    public function run(): void
    {
        $affairs = User::where('role', User::ROLE_GENERAL_AFFAIRS)->orderBy('id')->get();

        foreach (self::ORDERS as $i => [$officeCode, $supplierName, $requesterName, $status, $daysAgo, $supplierNote]) {
            $office = Office::where('code', $officeCode)->firstOrFail();
            $supplier = Supplier::where('name', $supplierName)->firstOrFail();

            // 申請用アカウント（営業所で共通のもの）から申請したことにする
            $account = User::where('office_id', $office->id)->where('is_manager', false)->firstOrFail();
            $manager = User::where('office_id', $office->id)->where('is_manager', true)->firstOrFail();
            $reviewer = $affairs[$i % $affairs->count()];

            // その業者の資材を2〜3件選ぶ
            $materials = Material::with('category')
                ->where('supplier_id', $supplier->id)
                ->where('is_active', true)
                ->orderBy('id')
                ->take($i % 2 === 0 ? 3 : 2)
                ->get();

            // 発注日（発注書を出した日）。総務の承認はその前日という想定
            $orderedAt = $daysAgo === null ? null : now()->subDays($daysAgo);
            $reviewedAt = $orderedAt?->copy()->subDay();

            $order = Order::create([
                'office_id' => $office->id,
                'supplier_id' => $supplier->id,
                'requested_by' => $account->id,
                'requester_name' => $requesterName,
                'status' => $status,
                'supplier_note' => $supplierNote,
                'desired_delivery_date' => ($orderedAt ?? now())->copy()->addDays(7),

                // 所長承認待ち以外は、所長の承認が済んでいる
                'manager_approved_by' => $status === Order::STATUS_PENDING_MANAGER ? null : $manager->id,
                'manager_approved_at' => $status === Order::STATUS_PENDING_MANAGER ? null : ($reviewedAt ?? now())->copy()->subDay(),

                // 発注待ち・発注済は総務の承認が済んでいる
                'reviewed_by' => in_array($status, [Order::STATUS_PENDING_ORDER, Order::STATUS_ORDERED], true) ? $reviewer->id : null,
                'reviewed_at' => $status === Order::STATUS_ORDERED ? $reviewedAt : ($status === Order::STATUS_PENDING_ORDER ? now()->subDay() : null),

                // 発注済は発注書を出している（＝実際に業者へ発注した）
                'ordered_by' => $status === Order::STATUS_ORDERED ? $reviewer->id : null,
                'ordered_at' => $status === Order::STATUS_ORDERED ? $orderedAt : null,

                // 却下は総務が理由付きで却下したことにする
                'rejected_by' => $status === Order::STATUS_REJECTED ? $reviewer->id : null,
                'reject_reason' => $status === Order::STATUS_REJECTED ? '在庫がまだ残っているため、次月にまとめて発注してください。' : null,
            ]);

            foreach ($materials as $n => $material) {
                // 最低ロットの倍数にする（ロット未設定なら適当な数量）
                $lot = $material->min_lot_qty;
                $quantity = $lot ? $lot * ($n + 1) : 100 * ($n + 1);

                $order->items()->create([
                    'material_id' => $material->id,
                    'material_name' => $material->name,
                    'category_id' => $material->category_id,
                    'category_name' => $material->category?->name,
                    'supplier_id' => $supplier->id,
                    'supplier_name' => $supplier->name,
                    'unit' => $material->unit,
                    'unit_price' => $material->unit_price,
                    'quantity' => $quantity,
                    'length_mm' => $material->length_mm,
                    'width_mm' => $material->width_mm,
                    'height_mm' => $material->height_mm,
                    'min_lot_qty' => $material->min_lot_qty,
                    'min_lot_unit' => $material->min_lot_unit,
                ]);
            }
        }
    }
}
