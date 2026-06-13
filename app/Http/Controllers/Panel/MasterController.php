<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;

class MasterController extends Controller
{
    protected array $tabs = ['page-builder'];

    public function index()
    {
        return redirect()->route('master.page-builder');
    }

    public function tab(string $tab)
    {
        if (!in_array($tab, $this->tabs)) {
            abort(404);
        }

        return view("panel.master.{$tab}", ['tab' => $tab]);
    }
}
