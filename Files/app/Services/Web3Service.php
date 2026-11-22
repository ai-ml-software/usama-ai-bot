<?php

namespace App\Services;

class Web3Service
{
    /**
     * Create BIP-39 word list JSON files for multiple languages.
     * @param string $storage_folder The folder where JSON files will be stored.
     * @return array Status and message of the operation
     */
    public static function createBip39WordListJsonFiles($storage_folder)
    {
        $languages = [
            'english',
            'chinese_simplified',
            'chinese_traditional',
            'french',
            'italian',
            'japanese',
            'korean',
            'spanish',
            'czech',
            'portuguese',
        ];

        try {
            foreach ($languages as $language) {
                $url = "https://raw.githubusercontent.com/bitcoin/bips/refs/heads/master/bip-0039/{$language}.txt";
                $contents = file_get_contents($url);
                $words = explode("\n", trim($contents));
                $words = array_map('trim', $words);
                $words = array_filter($words, fn($word) => !empty($word));

                $json_data = json_encode(array_values($words), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $file_path = $storage_folder . "/{$language}.json";
                if (!file_exists(dirname($file_path))) {
                    mkdir(dirname($file_path), 0755, true);
                }
                file_put_contents($file_path, $json_data);
            }
            return  [
                'status' => 'success',
                'message' => 'BIP-39 word list JSON files created successfully. Files are stored in: ' . $storage_folder,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Failed to create BIP-39 word list JSON files: ' . $e->getMessage(),
            ];
        }
    }


    /**
     * Validate a seed phrase against the BIP-39 word lists.
     * @param string $seed_phrase The seed phrase to validate.
     * @return bool True if valid, false otherwise.
     */

    public static function validateSeedPhrase($seed_phrase)
    {
        $words = explode(' ', trim($seed_phrase));
        $unique_words = array_unique($words);

        if (count($unique_words) < 12 || (count($unique_words) != 12 && count($unique_words) != 18 && count($unique_words) != 24)) {
            return false;
        }

        // Load all BIP-39 word lists
        $word_languages_files = glob(resource_path('json/bip-39/*.json'));
        $all_valid_words = [];
        foreach ($word_languages_files as $file) {
            $contents = file_get_contents($file);
            $words_array = json_decode($contents, true);
            $all_valid_words = array_merge($all_valid_words, $words_array);
        }

        // Check if all words are in the valid words list
        foreach ($unique_words as $word) {
            if (!in_array($word, $all_valid_words)) {
                return false;
            }
        }

        return true;
    }

}
