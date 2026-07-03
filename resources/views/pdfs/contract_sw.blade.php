<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Mkataba {{ $contract->contract_number }}</title>
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

    <!-- ===== UKURASA 1: MUHTASARI + WAHUSIKA ===== -->
    <div class="header">
        <h1>Mkataba wa Kukodisha Gari na Kununua kwa Malipo</h1>
        <div class="subtitle">Imeandaliwa kwa mujibu wa Sheria za Jamhuri ya Muungano wa Tanzania</div>
    </div>

    <div class="contract-number">Namba ya Mkataba: {{ $contract->contract_number }}</div>

    <div class="section">
        <div class="section-title">1. Wadau wa Mkataba</div>

        <div class="info-row">
            <span class="info-label">Mmiliki / Mkopeshaji:</span>
            <span class="info-value">{{ $contract->owner?->business_name ?? 'QuickWheels Ltd' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Anwani ya Mmiliki:</span>
            <span class="info-value">{{ $contract->owner?->address ?? 'Dar es Salaam, Tanzania' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Namba ya Usajili wa Kodi (TIN):</span>
            <span class="info-value">{{ $contract->owner?->tin_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dereva / Mpangaji:</span>
            <span class="info-value">{{ $contract->driver?->name ?? $contract->driver_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Namba ya Simu ya Dereva:</span>
            <span class="info-value">{{ $contract->driver?->phone ?? $contract->driver_phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Anwani ya Dereva:</span>
            <span class="info-value">{{ $contract->driver?->address ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Namba ya NIDA:</span>
            <span class="info-value">{{ $contract->driver?->nida_number ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Leseni ya Udereva:</span>
            <span class="info-value">{{ $contract->driver?->license_number ?? 'N/A' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">2. Maelezo ya Gari</div>

        <div class="info-row"><span class="info-label">Jina na Aina:</span><span class="info-value">{{ $contract->vehicle?->name ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Aina ya Gari:</span><span class="info-value">{{ $contract->vehicle?->type ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Mwaka:</span><span class="info-value">{{ $contract->vehicle?->year ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Namba ya Usajili:</span><span class="info-value">{{ $contract->vehicle?->registration ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Namba ya Chassis:</span><span class="info-value">{{ $contract->vehicle?->chassis_number ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Namba ya Injini:</span><span class="info-value">{{ $contract->vehicle?->engine_number ?? 'N/A' }}</span></div>
        <div class="info-row"><span class="info-label">Rangi:</span><span class="info-value">{{ $contract->vehicle?->color ?? 'N/A' }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">3. Masharti ya Mkataba</div>

        <div class="info-row">
            <span class="info-label">Aina ya Mkataba:</span>
            <span class="info-value">
                <span class="badge {{ $contract->contract_type === 'hire_purchase' ? 'badge-hire' : 'badge-rental' }}">
                    {{ $contract->contract_type === 'hire_purchase' ? 'KUNUNUA KWA MALIPO' : 'KUKODISHA' }}
                </span>
            </span>
        </div>
        <div class="info-row"><span class="info-label">Muda wa Malipo:</span><span class="info-value">{{ $contract->payment_frequency === 'weekly' ? 'Kila Wiki' : 'Kila Siku' }}</span></div>
        <div class="info-row"><span class="info-label">Tarehe ya Kuanza:</span><span class="info-value">{{ $contract->start_date->format('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Tarehe ya Kuisha:</span><span class="info-value">{{ $contract->end_date->format('d F Y') }}</span></div>
        <div class="info-row"><span class="info-label">Muda wa Mkataba:</span><span class="info-value">{{ $contract->start_date->diffInDays($contract->end_date) }} siku</span></div>
        <div class="info-row"><span class="info-label">Hali:</span><span class="info-value">{{ $contract->status_label }}</span></div>
    </div>

    <div class="section">
        <div class="section-title">4. Muhtasari wa Fedha</div>

        <table class="amount-table">
            <tr><td class="label">Jumla ya Thamani ya Mkataba (TZS)</td><td style="text-align:right">{{ number_format($contract->total_amount, 0) }} TZS</td></tr>
            @if($contract->deposit > 0)
            <tr><td class="label">Amana Iliyolipwa (TZS)</td><td style="text-align:right">{{ number_format($contract->deposit, 0) }} TZS</td></tr>
            @endif
            <tr><td class="label">Jumla Iliyolipwa Hadi Sasa (TZS)</td><td style="text-align:right">{{ number_format($totalPaid, 0) }} TZS</td></tr>
            <tr><td class="label">Salio (TZS)</td><td style="text-align:right">{{ number_format($remaining, 0) }} TZS</td></tr>
            <tr><td class="label">Hali ya Malipo</td><td style="text-align:right">{{ $remaining > 0 ? 'HAIJALIPWA KAMILI' : 'IMELIPWA KAMILI' }}</td></tr>
        </table>

        @if($contract->payment_frequency === 'weekly')
        <p style="font-size:10px; color:#555;"><strong>Malipo ya Kila Wiki:</strong> {{ number_format($contract->weekly_amount, 0) }} TZS</p>
        @else
        <p style="font-size:10px; color:#555;"><strong>Malipo ya Kila Siku:</strong> {{ number_format($contract->daily_amount, 0) }} TZS</p>
        @endif
        <p style="font-size:10px; color:#555;"><strong>Maendeleo:</strong> {{ $progress }}%</p>
    </div>

    <!-- ===== UKURASA 2: SHARTI NA MASHARTI ===== -->
    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">5. Sharti na Masharti</div>

        <p style="margin-bottom:12px; text-align:justify;">
            Mkataba huu unafanywa na kuingizwa kwa mujibu wa <strong>Sheria ya Mkataba, Cap. 345</strong>
            na <strong>Sheria ya Uuzaji wa Bidhaa, Cap. 214</strong> (Sheria za Tanzania), <strong>Sheria ya Bima ya Magari, Cap. 126</strong>,
            <strong>Sheria ya Usafirishaji Barabarani, Cap. 168</strong>, na sheria nyinginezo husika za Jamhuri ya Muungano wa Tanzania.
        </p>

        <div class="terms">
            <ol>
                <li>
                    <strong>Umiliki na Hatimiliki:</strong>
                    @if($contract->contract_type === 'hire_purchase')
                    Gari linabaki kuwa mali halisi ya Mmiliki/Mkopeshaji hadi malipo yote yatakapokamilika.
                    Mpangaji hatakuwa na haki ya kuuza, kuhamisha, kuweka rehani, au kutoa gari kwa namna yoyote
                    hadi umiliki uhamishwe baada ya malipo yote kukamilika.
                    @else
                    Gari linabaki kuwa mali halisi ya Mmiliki/Mkopeshaji wakati wote.
                    Mpangaji anakubali kuwa huu ni mkataba wa kukodisha na hakuna haki za umiliki zinazotolewa.
                    @endif
                </li>

                <li>
                    <strong>Wajibu wa Malipo:</strong>
                    Mpangaji atalipa kwa mujibu wa makubaliano ya {{ $contract->payment_frequency === 'weekly' ? 'kila wiki' : 'kila siku' }} bila kukosa.
                    Malipo yote yatafanywa kwa Shilingi za Tanzania (TZS).
                    Ucheleweshaji wa malipo utatoza adhabu ya <strong>5%</strong> ya kiasi kinachodaiwa kwa kila wiki ya kuchelewa.
                    Baada ya siku 30 za kutolipa, Mmiliki ana haki ya kumiliki gari tena bila notisi nyingine.
                </li>

                <li>
                    <strong>Matumizi ya Gari:</strong>
                    Gari litatumiwa tu ndani ya mipaka ya Jamhuri ya Muungano wa Tanzania isipokuwa
                    idhini ya maandishi imepatikana kutoka kwa Mmiliki. Gari halitatumika:
                    <br>- Kwa kusudi lolote haramu au kinyume na Sheria ya Usafirishaji Barabarani, Cap. 168
                    <br>- Kwa mbio za magari, majaribio ya mwendo kasi, au shindano lolote
                    <br>- Kubeba bidhaa au abiria kwa kodi au tuzo
                    <br>- Na mtu yeyote isipokuwa Mpangaji au wafanyakazi wake walioidhinishwa
                    <br>- Wakati Mpangaji yuko chini ya ushawishi wa pombe au dawa za kulevya
                </li>

                <li>
                    <strong>Matengenezo na Urekebishaji:</strong>
                    Mpangaji atawajibika kwa matengenezo yote ya kawaida ikiwa ni pamoja na
                    mafuta, matairi, breki, na utunzaji wa jumla. Matengenezo yote
                    yatafanywa na karakana iliyoidhinishwa na Mmiliki.
                    Mpangaji atatunza gari katika hali nzuri ya kufanya kazi na hatafanya
                    marekebisho yoyote bila idhini ya maandishi kutoka kwa Mmiliki.
                </li>

                <li>
                    <strong>Bima:</strong>
                    Gari litalindwa kwa bima kamili kwa mujibu wa Sheria ya Bima ya Magari, Cap. 126.
                    Gharama ya bima italipwa na
                    @if($contract->contract_type === 'hire_purchase') Mpangaji @else Mmiliki @endif.
                    Katika tukio la ajali, Mpangaji at:
                    <br>- Ripoti mara moja kwa kituo cha polisi kilicho karibu (pata Taarifa ya Polisi)
                    <br>- Asikubali dhima au kumaliza dai lolote bila idhini ya Mmiliki
                    <br>- Kubeba {{ number_format(500000, 0) }} TZS za kwanza za gharama zozote za ukarabati (makato)
                    <br>- Kuwajibika kwa upungufu wowote usiofunikwa na bima
                </li>

                <li>
                    <strong>Fidia na Dhima:</strong>
                    Mpangaji atamlinda Mmiliki dhidi ya madai yote, uharibifu,
                    hasara, na gharama zinazotokana na matumizi, matengenezo, au uendeshaji wa gari.
                    Mpangaji anachukua dhima kamili kwa:
                    <br>- Faini zote za trafiki, adhabu, na gharama za mahakama
                    <br>- Ada za barabara, kuegesha, na ushuru wa msongamano
                    <br>- Uharibifu wa mali ya watu wengine
                    <br>- Kujeruhi kwa watu wengine
                </li>

                <li>
                    <strong>Ufuatiliaji wa GPS:</strong>
                    Mpangaji anakubali kuwa gari limewekwa kifaa cha ufuatiliaji GPS.
                    Mmiliki ana haki ya kufuatilia eneo la gari wakati wote.
                    Kuchafua au kuondoa kifaa cha GPS kutachukuliwa kama ukiukaji mkubwa
                    wa mkataba huu na kutatoza adhabu ya <strong>TZS 1,000,000</strong>.
                </li>

                <li>
                    <strong>Kukosa Kulipa na Kumiliki Tena Gari:</strong>
                    Mmiliki anaweza kusitisha mkataba huu na kumiliki gari tena mara moja ikiwa:
                    <br>- Mpangaji atashindwa kulipa kwa siku 7 kutoka tarehe ya malipo
                    <br>- Mpangaji atakiuka sharti lolote la mkataba huu
                    <br>- Mpangaji atatoa taarifa za uongo
                    <br>- Gari linatumiwa kwa haramu au kwa madhumuni yaliyopigwa marufuku
                    <br>- Mpangaji atakuwa mfilisi au asiyeweza kulipa deni
                    <br><br>
                    Baada ya kumiliki gari tena, malipo yote yaliyotangulia yatatawaliwa kama kodi ya matumizi ya gari
                    na Mmiliki atakuwa na haki ya kupata kiasi chochote kinachodaiwa.
                </li>

                <li>
                    <strong>Kusitisha Mapema:</strong>
                    @if($contract->contract_type === 'hire_purchase')
                    Mpangaji anaweza kukamilisha ununuzi wa gari mapema kwa kulipa salio lote.
                    Baada ya malipo kamili, umiliki utahamishiwa kwa Mpangaji ndani ya siku 14.
                    @else
                    Pande zozote zinaweza kusitisha mkataba huu wa kukodisha kwa notisi ya siku 7 kwa maandishi.
                    @endif
                </li>

                <li>
                    <strong>Nguvu za Mungu (Force Majeure):</strong>
                    Hakuna upande utakaowajibika kwa kushindwa kutimiza wajibu kutokana na matukio
                    yaliyo nje ya uwezo wa kawaida ikiwa ni pamoja na majanga ya asili, vita, ugaidi,
                    migomo, kanuni za serikali, au majanga ya asili.
                </li>

                <li>
                    <strong>Sheria na Mamlaka ya Mahakama:</strong>
                    Mkataba huu utatawaliwa na kufasiriwa kwa mujibu wa sheria za
                    Jamhuri ya Muungano wa Tanzania. Mizozo yoyote inayotokana na mkataba huu
                    itapelekwa kwa mamlaka ya pekee ya mahakama za Tanzania.
                    Pande hizo zinajisalimisha kwa mamlaka ya <strong>Mahakama ya Hakimu Mkazi</strong>
                    au <strong>Mahakama Kuu ya Tanzania</strong> kama itakavyostahili.
                </li>

                <li>
                    <strong>Mkataba Mzima:</strong>
                    Mkataba huu unajumuisha makubaliano yote kati ya pande. Hakuna tofauti,
                    marekebisho, au msamaha wa sharti lolote litakalokuwa halali isipokuwa kwa maandishi
                    na kutiwa saini na pande zote mbili.
                </li>
            </ol>
        </div>
    </div>

    <!-- ===== UKURASA 3: SAINI ===== -->
    <div class="page-break"></div>

    <div class="section">
        <div class="section-title">6. Saini</div>

        <p style="text-align:justify; margin-bottom:20px;">
            KWA UTHIBITISHO WA HAYO, pande zimetia saini mkataba huu tarehe
            {{ $contract->start_date->format('d') }} {{ $contract->start_date->format('F Y') }}.
        </p>

        <div class="signatures">
            <div class="signature-row">
                <div class="signature-box">
                    <p><strong>IMETIWA SAINI na MMILIKI/MKOPESHAJI:</strong></p>
                    <p>{{ $contract->owner?->business_name ?? 'QuickWheels Ltd' }}</p>
                    <div class="line">Saini &amp; Tarehe</div>
                </div>
                <div class="signature-box">
                    <p><strong>IMETIWA SAINI na MPANGAJI/DEREVA:</strong></p>
                    <p>{{ $contract->driver?->name ?? $contract->driver_name }}</p>
                    <div class="line">Saini &amp; Tarehe</div>
                </div>
            </div>

            <div style="margin-top:20px; padding-top:16px; border-top:1px solid #ddd;">
                <p style="font-size:10px; color:#555;">
                    <strong>Shahidi 1:</strong><br>
                    Jina: ___________________________<br>
                    Saini: ___________________________<br>
                    Tarehe: ___________________________
                </p>
                <p style="font-size:10px; color:#555; margin-top:12px;">
                    <strong>Shahidi 2:</strong><br>
                    Jina: ___________________________<br>
                    Saini: ___________________________<br>
                    Tarehe: ___________________________
                </p>
            </div>
        </div>
    </div>

    <div class="footer">
        Mkataba {{ $contract->contract_number }} &mdash; Imeandaliwa {{ now()->format('d F Y H:i') }} &mdash; Mfumo wa Usimamizi wa Magari wa QuickWheels
    </div>

</body>
</html>
