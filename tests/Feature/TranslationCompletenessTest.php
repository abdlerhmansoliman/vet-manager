<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class TranslationCompletenessTest extends TestCase
{
    public function test_english_and_arabic_dictionaries_have_exact_key_parity(): void
    {
        $enPath = base_path('lang/en.json');
        $arPath = base_path('lang/ar.json');

        $this->assertFileExists($enPath, 'lang/en.json must exist');
        $this->assertFileExists($arPath, 'lang/ar.json must exist');

        $en = json_decode((string) file_get_contents($enPath), true, 512, JSON_THROW_ON_ERROR);
        $ar = json_decode((string) file_get_contents($arPath), true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($en);
        $this->assertIsArray($ar);

        $missingInAr = array_diff_key($en, $ar);
        $missingInEn = array_diff_key($ar, $en);

        $this->assertEmpty(
            $missingInAr,
            'The following keys are in lang/en.json but missing from lang/ar.json: '.implode(', ', array_keys($missingInAr))
        );

        $this->assertEmpty(
            $missingInEn,
            'The following keys are in lang/ar.json but missing from lang/en.json: '.implode(', ', array_keys($missingInEn))
        );

        foreach ($en as $key => $val) {
            $this->assertNotEmpty($val, "Value for key [{$key}] in lang/en.json cannot be empty");
        }

        foreach ($ar as $key => $val) {
            $this->assertNotEmpty($val, "Value for key [{$key}] in lang/ar.json cannot be empty");
        }
    }

    public function test_stitch_implemented_vue_components_have_all_translation_keys_in_dictionaries(): void
    {
        $components = [
            resource_path('js/Layouts/ClinicLayout.vue'),
            resource_path('js/Pages/Dashboard.vue'),
            resource_path('js/Pages/Staff/Index.vue'),
            resource_path('js/Pages/Visits/Show.vue'),
            resource_path('js/Components/MedicalAlertBanner.vue'),
            resource_path('js/Pages/Clients/Create.vue'),
            resource_path('js/Pages/Clients/Index.vue'),
            resource_path('js/Pages/Patients/Index.vue'),
            resource_path('js/Pages/Inventory/Create.vue'),
            resource_path('js/Pages/Inventory/Index.vue'),
        ];

        $en = json_decode((string) file_get_contents(base_path('lang/en.json')), true, 512, JSON_THROW_ON_ERROR);
        $ar = json_decode((string) file_get_contents(base_path('lang/ar.json')), true, 512, JSON_THROW_ON_ERROR);

        $missingKeys = [];

        foreach ($components as $filePath) {
            $this->assertFileExists($filePath);
            $content = (string) file_get_contents($filePath);

            // Match $t('...') and trans('...') with single quotes
            preg_match_all("/(?:\$t|trans)\(\s*'([^']+)'/u", $content, $singleQuotes);
            // Match $t("...") and trans("...") with double quotes
            preg_match_all('/(?:\$t|trans)\(\s*"([^"]+)"/u', $content, $doubleQuotes);

            $extractedKeys = array_unique(array_merge($singleQuotes[1] ?? [], $doubleQuotes[1] ?? []));

            foreach ($extractedKeys as $key) {
                if (! array_key_exists($key, $en) || ! array_key_exists($key, $ar)) {
                    $missingKeys[] = '['.basename($filePath)."] {$key}";
                }
            }
        }

        $this->assertEmpty(
            $missingKeys,
            'The following extracted translation keys are missing from lang/en.json or lang/ar.json: '.implode("\n", $missingKeys)
        );
    }
}
