<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class ContractPdfController extends Controller
{
    private function getView($locale)
    {
        return $locale === 'sw' ? 'pdfs.contract_sw' : 'pdfs.contract';
    }

    private function buildPdf($id, $locale)
    {
        $contract = Contract::with(['driver', 'vehicle', 'owner', 'payments'])->findOrFail($id);

        $pdf = Pdf::loadView($this->getView($locale), [
            'contract' => $contract,
            'totalPaid' => $contract->payments->sum('amount'),
            'remaining' => max(0, $contract->total_amount - $contract->payments->sum('amount')),
            'progress' => $contract->total_amount > 0
                ? round(($contract->payments->sum('amount') / $contract->total_amount) * 100)
                : 0,
        ]);

        $pdf->setPaper('A4', 'portrait');
        $pdf->setOptions([
            'defaultFont' => 'dejavu sans',
            'isRemoteEnabled' => true,
            'isHtml5ParserEnabled' => true,
        ]);

        return [$pdf, $contract];
    }

    public function download(Request $request, $id)
    {
        $locale = $request->query('lang', 'en');
        [$pdf, $contract] = $this->buildPdf($id, $locale);
        return $pdf->download('contract_' . $contract->contract_number . '.pdf');
    }

    public function preview(Request $request, $id)
    {
        $locale = $request->query('lang', 'en');
        [$pdf, $contract] = $this->buildPdf($id, $locale);
        return $pdf->stream('contract_' . $contract->contract_number . '.pdf');
    }
}
