<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;

/**
 * 直前の検索条件をセッションに覚えておき、詳細・編集画面から一覧へ戻るときに復元する。
 *
 * 一覧 → 詳細（編集）→ 戻る、で検索条件が消えると探し直しになるため。
 * 一覧の index() で rememberSearch()、戻り先が要る場所で lastSearchUrl() を呼ぶ。
 *
 * 条件は **クエリ文字列のまま** 持つ。配列にすると「すべて＝空」が
 * ConvertEmptyStringsToNull で null になってURLの組み立て時に消え、
 * 「条件なし＝初回表示」とみなされて既定値が再適用されてしまうため。
 */
trait RemembersLastSearch
{
    /** セッションキー。コントローラーごとに分ける */
    private function lastSearchKey(): string
    {
        return static::class . '.last_search';
    }

    /** いまの検索条件を覚える（一覧の index() で呼ぶ） */
    protected function rememberSearch(Request $request): void
    {
        $request->session()->put($this->lastSearchKey(), $request->getQueryString());
    }

    /**
     * 覚えている検索条件つきの一覧URL。
     * 何も覚えていなければ（メールのリンクなどから直接来た場合）素の一覧を返す。
     */
    protected function lastSearchUrl(Request $request, string $routeName): string
    {
        $query = $request->session()->get($this->lastSearchKey());

        return route($routeName) . ($query ? '?' . $query : '');
    }
}
