<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Contract {{ $contract->contract_number }}</title>
    <style>
        @page { margin: 25mm 20mm 25mm 20mm; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.6;
            color: #1a1a2e;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #1a1a2e;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h1 {
            font-size: 18px;
            font-weight: 700;
            margin: 0 0 4px;
            text-transform: uppercase;
        }
        .header .subtitle {
            font-size: 11px;
            color: #555;
        }
        .contract-number {
            text-align: right;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 20px;
            color: #1a1a2e;
        }
        .section {
            margin-bottom: 18px;
            page-break-inside: avoid;
        }
        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            border-bottom: 1px solid #1a1a2e;
            padding-bottom: 4px;
            margin-bottom: 10px;
            color: #1a1a2e;
        }
        .info-row {
            display: flex;
            padding: 3px 0;
            border-bottom: 1px dotted #ddd;
        }
        .info-label {
            width: 180px;
            font-weight: 600;
            color: #333;
        }
        .info-value {
            flex: 1;
            color: #1a1a2e;
        }
        .amount-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        .amount-table td {
            padding: 6px 10px;
            border: 1px solid #ccc;
        }
        .amount-table .label { font-weight: 600; background: #f5f5f5; }
        .terms {
            margin-top: 20px;
        }
        .terms ol {
            padding-left: 20px;
        }
        .terms li {
            margin-bottom: 8px;
            text-align: justify;
        }
        .signatures {
            margin-top: 36px;
            page-break-inside: avoid;
        }
        .signature-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }
        .signature-box {
            width: 45%;
        }
        .signature-box .line {
            border-top: 1px solid #1a1a2e;
            margin-top: 50px;
            padding-top: 6px;
            text-align: center;
            font-weight: 600;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #888;
            border-top: 1px solid #ddd;
            padding-top: 6px;
        }
        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 700;
        }
        .badge-hire { background: #1e3a5f; color: #fff; }
        .badge-rental { background: #2d6a4f; color: #fff; }
        .page-break { page-break-before: always; }
        .clause { margin-bottom: 6px; }
        .clause strong { display: block; }
    </style>
</head>
<body>

    <!-- ===== PAGE 1: SUMMARY + PARTIES ===== -->
    <div class="header">
        <h1>Motor Vehicle Hire Purchase &amp; Rental Agreement</h1>
        <div class="subtitle">Prepared in accordance with the Laws of the United Republic of Tanzania</div>
    </div>

    <div class="contract-number">Contract No: {{ $contract->contract_number }}</div>

    <div class="section">
        <div class="section-title">1. Parties to the Agreement</div>

        <div class="info-row">
            <span class="info-label">Owner / Lessor:</span>
            <span class="info-value">{{ $contract->owner?->business_name ?? 'QuickWheels Ltd' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Owner Address:</span>
            <span class="info-value">{{ $contract->owner?->address ?? 'Dar es Salaam, Tanzania' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Owner TIN:</span>
            <span class="info-value">{{ $contract->owner?->tin_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Driver / Hirer:</span>
            <span class="info-value">{{ $contract->driver?->name ?? $contract->driver_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Driver Phone:</span>
            <span class="info-value">{{ $contract->driver?->phone ?? $contract->driver_phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Driver Address:</span>
            <span class="info-value">{{ $contract->driver?->address ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">NIDA Number:</span>
            <span class="info-value">{{ $contract->driver?->nida_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Driving License:</span>
            <span class="info-value">{{ $contract->driver?->license_number ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. Vehicle Description</div>

        <div class="info-row"><span class="info-label">Make &amp; Model:</span><span class="info-value">{{ $contract->vehicle?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Type:</span><span class="info-value">{{ $contract->vehicle?->type ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Year:</span><span class="info-value">{{ $contract->vehicle?->year ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Registration:</span><span class="info-value">{{ $contract->vehicle?->registration ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Chassis No:</span><span class="info-value">{{ $contract->vehicle?->chassis_number ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Engine No:</span><span class="info-value">{{ $contract->vehicle?->engine_number ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Colour:</span><span class="info-value">{{ $contract->vehicle?->color ?? 'N/A' }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">3. Contract Terms</div>

        <div class="info-row">
            <span class="info-label">Contract Type:</span>
            <span class="info-value">
                <span class="badge {{ $contract->contract_type === 'hire_purchase' ? 'badge-hire' : 'badge-rental' }}">
                    {{ $contract->contract_type === 'hire_purchase' ? 'HIRE PURCHASE' : 'RENTAL' }}
                </span>
            </span>
        </div>
        <div class="info-row"><span class="info-label">Payment Frequency:</span><span class="info-value">{{ ucfirst($contract->payment_frequency) }}</span></div>
        <div class="info-row"><span class="info-label">Commencement Date:</span><span class="info-value">{{ $contract->start_date->format('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Expiry Date:</span><span class="info-value">{{ $contract->end_date->format('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Duration:</span><span class="info-value">{{ $contract->start_date->diffInDays($contract->end_date) }} days</span></div>
        <div class="info-row"><span class="info-label">Status:</span><span class="info-value">{{ $contract->status_label }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">4. Financial Summary</div>

        <table class="amount-table">
            <tr><td class="label">Total Contract Value (TZS)</td><td style="text-align:right">{{ number_format($contract->total_amount, 0) }} TZS</td></tr>
            @if($contract->deposit > 0)
            <tr><td class="label">Deposit Paid (TZS)</td><td style="text-align:right">{{ number_format($contract->deposit, 0) }} TZS</td></tr>
            @endif
            <tr><td class="label">Total Paid To Date (TZS)</td><td style="text-align:right">{{ number_format($totalPaid, 0) }} TZS</td></tr>
            <tr><td class="label">Remaining Balance (TZS)</td><td style="text-align:right">{{ number_format($remaining, 0) }} TZS</td></tr>
            <tr><td class="label">Payment Status</td><td style="text-align:right">{{ $remaining > 0 ? 'OUTSTANDING' : 'FULLY PAID' }}</td></tr>
        </table>

        @if($contract->payment_frequency === 'weekly')
        <p style="font-size:10px; color:#555;"><strong>Weekly Instalment:</strong> {{ number_format($contract->weekly_amount, 0) }} TZS</p>
        @else
        <p style="font-size:10px; color:#555;"><strong>Daily Instalment:</strong> {{ number_format($contract->daily_amount, 0) }} TZS</p>
        @endif
        <p style="font-size:10px; color:#555;"><strong>Progress:</strong> {{ $progress }}% complete</p>
    </div>

    <!-- ===== PAGE 2: TERMS AND CONDITIONS ===== -->
    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">5. Terms and Conditions</div>

        <p style="margin-bottom:12px; text-align:justify;">
            This Agreement is made and entered into in accordance with the <strong>Law of Contract Act, Cap. 345</strong>
            and the <strong>Sale of Goods Act, Cap. 214</strong> (Laws of Tanzania), the <strong>Motor Vehicles (Insurance) Act, Cap. 126</strong>,
            the <strong>Road Traffic Act, Cap. 168</strong>, and all other relevant statutes of the United Republic of Tanzania.
        </p>

        <div class="terms">
            <ol>
                <li>
                    <strong>Ownership and Title:</strong>
                    @if($contract->contract_type === 'hire_purchase')
                    The vehicle remains the sole property of the Owner/Lessor until full payment of all instalments.
                    The Hirer shall have no right to sell, transfer, pledge, or otherwise dispose of the vehicle
                    until full ownership is transferred upon completion of all payments.
                    @else
                    The vehicle remains the sole property of the Owner/Lessor at all times.
                    The Hirer acknowledges that this is a rental agreement and no ownership rights are conferred.
                    @endif
                </li>

                <li>
                    <strong>Payment Obligations:</strong>
                    The Hirer shall pay the agreed {{ $contract->payment_frequency }} instalments without fail.
                    All payments are to be made in Tanzanian Shillings (TZS).
                    Late payment shall attract a penalty of <strong>5%</strong> of the outstanding amount per week of delay.
                    After 30 days of non-payment, the Owner reserves the right to repossess the vehicle without further notice.
                </li>

                <li>
                    <strong>Use of Vehicle:</strong>
                    The vehicle shall only be used within the borders of the United Republic of Tanzania unless
                    prior written consent is obtained from the Owner. The vehicle shall not be used:
                    <br>- For any illegal purpose or in violation of the Road Traffic Act, Cap. 168
                    <br>- For racing, pace-making, or speed trials
                    <br>- To carry goods or passengers for hire or reward
                    <br>- By any person other than the Hirer or the Hirer's authorised employees
                    <br>- While the Hirer is under the influence of alcohol or drugs
                </li>

                <li>
                    <strong>Maintenance and Repairs:</strong>
                    The Hirer shall be responsible for all routine maintenance including but not limited to
                    oil changes, tyre replacements, brake servicing, and general upkeep. All maintenance
                    shall be carried out by a licensed garage approved by the Owner.
                    The Hirer shall keep the vehicle in good working condition and shall not
                    make any modifications without prior written consent.
                </li>

                <li>
                    <strong>Insurance:</strong>
                    The vehicle shall be insured comprehensively under the Motor Vehicles (Insurance) Act, Cap. 126.
                    The cost of insurance shall be borne by the
                    @if($contract->contract_type === 'hire_purchase') Hirer @else Owner @endif.
                    In the event of an accident, the Hirer shall:
                    <br>- Immediately report to the nearest police station (obtain a Police Abstract)
                    <br>- Not admit liability or settle any claim without the Owner's consent
                    <br>- Bear the first {{ number_format(500000, 0) }} TZS of any repair costs (excess)
                    <br>- Be liable for any shortfall not covered by insurance
                </li>

                <li>
                    <strong>Indemnity and Liability:</strong>
                    The Hirer shall indemnify and keep the Owner harmless against all claims, damages,
                    losses, and expenses arising from the use, maintenance, or operation of the vehicle.
                    The Hirer assumes full responsibility for:
                    <br>- All traffic fines, penalties, and court costs
                    <br>- Tolls, parking fees, and congestion charges
                    <br>- Damage to third-party property
                    <br>- Injury to third parties
                </li>

                <li>
                    <strong>GPS Tracking:</strong>
                    The Hirer acknowledges that the vehicle is equipped with a GPS tracking device.
                    The Owner reserves the right to monitor the vehicle's location at all times.
            Tampering with or removing the GPS device shall constitute a material breach
                    of this agreement and shall attract a penalty of <strong>1,000,000 TZS</strong>.
                </li>

                <li>
                    <strong>Default and Repossession:</strong>
                    The Owner may terminate this agreement and repossess the vehicle immediately if:
                    <br>- The Hirer fails to pay any instalment within 7 days of the due date
                    <br>- The Hirer breaches any term of this agreement
                    <br>- The Hirer provides false information
                    <br>- The vehicle is used illegally or for prohibited purposes
                    <br>- The Hirer becomes bankrupt or insolvent
                    <br><br>
                    Upon repossession, all prior payments shall be forfeited as rental for use of the vehicle
                    and the Owner shall be entitled to recover any outstanding amounts.
                </li>

                <li>
                    <strong>Early Termination:</strong>
                    @if($contract->contract_type === 'hire_purchase')
                    The Hirer may complete the hire purchase early by paying the full outstanding balance.
                    Upon full payment, ownership shall transfer to the Hirer within 14 days.
                    @else
                    Either party may terminate this rental agreement by giving 7 days' written notice.
                    @endif
                </li>

                <li>
                    <strong>Force Majeure:</strong>
                    Neither party shall be liable for failure to perform obligations due to events beyond
                    reasonable control including but not limited to acts of God, war, terrorism, strikes,
                    government regulations, or natural disasters.
                </li>

                <li>
                    <strong>Governing Law and Jurisdiction:</strong>
                    This Agreement shall be governed by and construed in accordance with the laws of the
                    United Republic of Tanzania. Any dispute arising out of or in connection with this
                    Agreement shall be subject to the exclusive jurisdiction of the courts of Tanzania.
                    The parties hereby submit to the jurisdiction of the <strong>Resident Magistrate's Court</strong>
                    or the <strong>High Court of Tanzania</strong> as appropriate.
                </li>

                <li>
                    <strong>Entire Agreement:</strong>
                    This Agreement constitutes the entire agreement between the parties. No variation,
                    modification, or waiver of any term shall be valid unless in writing and signed by both parties.
                </li>
            </ol>
        </div>
    </div>

    <!-- ===== PAGE 3: SIGNATURES ===== -->
    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">6. Signatures</div>

        <p style="text-align:justify; margin-bottom:20px;">
            IN WITNESS WHEREOF, the parties have executed this Agreement on the
            {{ $contract->start_date->format('d') }} day of {{ $contract->start_date->format('F Y') }}.
        </p>

        <div class="signatures">
            <div class="signature-row">
                <div class="signature-box">
                    <p><strong>SIGNED by the OWNER/LESSOR:</strong></p>
                    <p>{{ $contract->owner?->business_name ?? 'QuickWheels Ltd' }}</p>
                    <div class="line">Signature &amp; Date</div>
                </div>
                <div class="signature-box">
                    <p><strong>SIGNED by the HIRER/DRIVER:</strong></p>
                    <p>{{ $contract->driver?->name ?? $contract->driver_name }}</p>
                    <div class="line">Signature &amp; Date</div>
                </div>
            </div>

            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #ddd;">
                <p style="font-size:10px; color:#555;">
                    <strong>Witness 1:</strong><br>
                    Name: ___________________________<br>
                    Signature: ___________________________<br>
                    Date: ___________________________
                </p>
                <p style="font-size:10px; color:#555; margin-top:12px;">
                    <strong>Witness 2:</strong><br>
                    Name: ___________________________<br>
                    Signature: ___________________________<br>
                    Date: ___________________________
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        Contract {{ $contract->contract_number }} &mdash; Generated on {{ now()->format('d F Y H:i') }} &mdash; QuickWheels Fleet Management System
    </div>

</body>
</html>
