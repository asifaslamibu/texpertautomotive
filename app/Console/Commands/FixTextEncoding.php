<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Repairs product text that was imported as UTF-8 but read as Windows-1252,
 * which is why bullets show up as "â€¢" and quotes as "â€™".
 *
 * The damage is reversible: those characters are the original bytes, one per
 * character, so re-reading them as bytes gives the real text back. A field that
 * contains anything Windows-1252 never had is left untouched rather than guessed at.
 */
class FixTextEncoding extends Command
{
    protected $signature = 'content:fix-encoding {--apply : write the repaired text (otherwise this is only a preview)}';

    protected $description = 'Fix mangled symbols (â€¢, â€™, Ã©) in product names, descriptions and specs.';

    public function handle(): int
    {
        $fields = array_values(array_filter(
            Schema::getColumnListing('products'),
            fn ($c) => in_array($c, ['name', 'short_description', 'description', 'brand', 'uom', 'dimensions'], true)
                || str_starts_with($c, 'spec_')
        ));

        $apply = $this->option('apply');
        $fixedFields = 0;
        $fixedRows = 0;
        $samples = [];

        Product::query()
            ->where(function ($q) use ($fields) {
                foreach ($fields as $f) {
                    $q->orWhere($f, 'like', '%â€%')->orWhere($f, 'like', '%Ã%')->orWhere($f, 'like', '%Â%');
                }
            })
            ->select(array_merge(['id'], $fields))
            ->chunkById(300, function ($chunk) use ($fields, $apply, &$fixedFields, &$fixedRows, &$samples) {
                foreach ($chunk as $product) {
                    $updates = [];
                    foreach ($fields as $field) {
                        $value = $product->{$field};
                        if (! is_string($value) || $value === '' || ! self::looksMangled($value)) {
                            continue;
                        }
                        $fixed = self::repair($value);
                        if ($fixed !== null && $fixed !== $value) {
                            $updates[$field] = $fixed;
                            $fixedFields++;
                            if (count($samples) < 10) {
                                $samples[] = [$product->id, $field, self::snippet($value), self::snippet($fixed)];
                            }
                        }
                    }
                    if ($updates) {
                        $fixedRows++;
                        if ($apply) {
                            Product::where('id', $product->id)->update($updates);
                        }
                    }
                }
            });

        if ($samples) {
            $this->table(['Product', 'Field', 'Before', 'After'], $samples);
        }

        $this->info(($apply ? 'Fixed ' : 'Would fix ')."{$fixedFields} field(s) across {$fixedRows} product(s).");
        if (! $apply && $fixedRows) {
            $this->line('Run it again with --apply to save the changes.');
        }

        return self::SUCCESS;
    }

    private static function looksMangled(string $value): bool
    {
        return str_contains($value, 'â€') || str_contains($value, 'Ã') || str_contains($value, 'Â');
    }

    /** Undo the mis-read, up to three times for text that was mangled more than once. */
    private static function repair(string $value): ?string
    {
        $current = $value;
        for ($pass = 0; $pass < 3; $pass++) {
            $next = self::decodeOnce($current);
            if ($next === null || $next === $current) {
                break;
            }
            $current = $next;
            if (! self::looksMangled($current)) {
                break;
            }
        }

        return $current === $value ? null : $current;
    }

    /** One pass: every character back to the single Windows-1252 byte it came from. */
    private static function decodeOnce(string $value): ?string
    {
        static $map = null;
        if ($map === null) {
            $map = [];
            for ($byte = 0x80; $byte <= 0xFF; $byte++) {
                $char = mb_convert_encoding(chr($byte), 'UTF-8', 'Windows-1252');
                if ($char !== '' && $char !== null) {
                    $map[$char] = chr($byte);
                }
            }
        }

        $chars = preg_split('//u', $value, -1, PREG_SPLIT_NO_EMPTY);
        if ($chars === false) {
            return null;
        }

        $out = '';
        foreach ($chars as $char) {
            if (strlen($char) === 1) {
                $out .= $char;              // plain ASCII, unchanged
            } elseif (isset($map[$char])) {
                $out .= $map[$char];        // a mis-read byte, put back
            } else {
                return null;                // never came from Windows-1252 — leave this field alone
            }
        }

        return mb_check_encoding($out, 'UTF-8') ? $out : null;
    }

    private static function snippet(string $value): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)));

        return mb_substr($value, 0, 48);
    }
}
