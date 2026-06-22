<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class SupportedBanks
{
    /**
     * @return list<string>
     */
    public static function options(): array
    {
        return [
            self::label('ABBANK', 'Ng&#226;n h&#224;ng TMCP An B&#236;nh'),
            self::label('ACB', 'Ng&#226;n h&#224;ng TMCP &#193; Ch&#226;u'),
            self::label('AGRIBANK', 'Ng&#226;n h&#224;ng N&#244;ng nghi&#7879;p v&#224; Ph&#225;t tri&#7875;n N&#244;ng th&#244;n Vi&#7879;t Nam'),
            self::label('BACABANK', 'Ng&#226;n h&#224;ng TMCP B&#7855;c &#193;'),
            self::label('BAOVIETBANK', 'Ng&#226;n h&#224;ng B&#7843;o Vi&#7879;t'),
            self::label('BIDV', 'Ng&#226;n h&#224;ng TMCP &#272;&#7847;u t&#432; v&#224; Ph&#225;t tri&#7875;n Vi&#7879;t Nam'),
            self::label('CAKE', 'Ng&#226;n h&#224;ng s&#7889; CAKE By VPBank'),
            self::label('DONGA BANK', 'Ng&#226;n h&#224;ng TMCP &#272;&#244;ng &#193;'),
            self::label('EXIMBANK', 'Ng&#226;n h&#224;ng TMCP Xu&#7845;t Nh&#7853;p kh&#7849;u Vi&#7879;t Nam'),
            self::label('HDBANK', 'Ng&#226;n h&#224;ng TMCP Ph&#225;t tri&#7875;n Th&#224;nh ph&#7889; H&#7891; Ch&#237; Minh'),
            self::label('LIENVIETPOSTBANK', 'Ng&#226;n h&#224;ng TMCP B&#432;u &#272;i&#7879;n Li&#234;n Vi&#7879;t'),
            self::label('MB BANK', 'Ng&#226;n h&#224;ng TMCP Qu&#226;n &#273;&#7897;i'),
            self::label('MSB', 'Ng&#226;n h&#224;ng TMCP H&#224;ng H&#7843;i'),
            self::label('NAM A BANK', 'Ng&#226;n h&#224;ng TMCP Nam &#193;'),
            self::label('OCB', 'Ng&#226;n h&#224;ng TMCP Ph&#432;&#417;ng &#272;&#244;ng'),
            self::label('OCEANBANK', 'Ng&#226;n h&#224;ng Th&#432;&#417;ng m&#7841;i TNHH MTV &#272;&#7841;i D&#432;&#417;ng'),
            self::label('PVCOMBANK', 'Ng&#226;n h&#224;ng TMCP &#272;&#7841;i Ch&#250;ng Vi&#7879;t Nam'),
            self::label('SACOMBANK', 'Ng&#226;n h&#224;ng TMCP S&#224;i G&#242;n Th&#432;&#417;ng T&#237;n'),
            self::label('SCB', 'Ng&#226;n h&#224;ng TMCP S&#224;i G&#242;n'),
            self::label('SEABANK', 'Ng&#226;n h&#224;ng TMCP &#272;&#244;ng Nam &#193;'),
            self::label('SHB', 'Ng&#226;n h&#224;ng TMCP S&#224;i G&#242;n - H&#224; N&#7897;i'),
            self::label('SHINHANBANK', 'Ng&#226;n h&#224;ng TNHH MTV Shinhan Vi&#7879;t Nam'),
            self::label('TECHCOMBANK', 'Ng&#226;n h&#224;ng TMCP K&#7929; th&#432;&#417;ng Vi&#7879;t Nam'),
            self::label('TPBANK', 'Ng&#226;n h&#224;ng TMCP Ti&#234;n Phong'),
            self::label('VIB', 'Ng&#226;n h&#224;ng TMCP Qu&#7889;c t&#7871; Vi&#7879;t Nam'),
            self::label('VIETABANK', 'Ng&#226;n h&#224;ng TMCP Vi&#7879;t &#193;'),
            self::label('VIETCAPITALBANK', 'Ng&#226;n h&#224;ng TMCP B&#7843;n Vi&#7879;t'),
            self::label('VIETCOMBANK', 'Ng&#226;n h&#224;ng TMCP Ngo&#7841;i Th&#432;&#417;ng Vi&#7879;t Nam'),
            self::label('VIETINBANK', 'Ng&#226;n h&#224;ng TMCP C&#244;ng th&#432;&#417;ng Vi&#7879;t Nam'),
            self::label('VPBANK', 'Ng&#226;n h&#224;ng TMCP Vi&#7879;t Nam Th&#7883;nh V&#432;&#7907;ng'),
        ];
    }

    public static function byCode(string $code): ?string
    {
        $normalizedCode = strtoupper(trim($code));

        foreach (self::options() as $option) {
            if (str_starts_with($option, $normalizedCode.' - ')) {
                return $option;
            }
        }

        return null;
    }

    public static function normalize(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (in_array($trimmed, self::options(), true)) {
            return $trimmed;
        }

        $aliases = self::legacyAliases();
        $key = strtoupper($trimmed);

        return $aliases[$key] ?? null;
    }

    /**
     * @return array<int, string|Rule>
     */
    public static function validationRules(): array
    {
        return ['required', 'string', Rule::in(self::options())];
    }

    private static function label(string $code, string $label): string
    {
        return $code.' - '.html_entity_decode($label);
    }

    /**
     * @return array<string, string>
     */
    private static function legacyAliases(): array
    {
        $aliases = [];

        foreach (self::options() as $option) {
            $aliases[strtoupper($option)] = $option;
            [$code] = explode(' - ', $option, 2);
            $aliases[strtoupper($code)] = $option;
        }

        $aliases['MBBANK'] = self::byCode('MB BANK');
        $aliases['MB BANK'] = self::byCode('MB BANK');
        $aliases['VIETCOMBANK'] = self::byCode('VIETCOMBANK');
        $aliases['VIETCOM BANK'] = self::byCode('VIETCOMBANK');
        $aliases['VCB'] = self::byCode('VIETCOMBANK');
        $aliases['VIETINBANK'] = self::byCode('VIETINBANK');
        $aliases['TECHCOMBANK'] = self::byCode('TECHCOMBANK');
        $aliases['TPBANK'] = self::byCode('TPBANK');
        $aliases['VPBANK'] = self::byCode('VPBANK');
        $aliases['SHINHAN BANK'] = self::byCode('SHINHANBANK');

        return array_filter($aliases);
    }
}
