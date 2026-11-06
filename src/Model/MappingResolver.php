<?php

declare(strict_types=1);

namespace Kalakotra\EFormKinderzuschlag\Model;

/**
 * Resolves a field mapping like BaseSubmission::getArrayDataForPDF() does for $field_mapping, for the additional
 * documents of a record (e.g. KiZ 5c on SubmissionChild, KiZ 1-AnA on Submission):
 *   'PDF field' => 'DbField'              plain value
 *   'PDF field' => 'Method:DbField:Arg'   $record->Method('DbField', 'Arg')
 */
final class MappingResolver
{
    /**
     * @param array<string, string> $mapping
     * @return array<string, mixed>
     */
    public static function resolve(object $record, array $mapping): array
    {
        $data = [];

        foreach ($mapping as $pdfField => $source) {
            $source = (string) $source;

            if (str_contains($source, ':')) {
                [$method, $fieldName, $argument] = array_pad(explode(':', $source, 3), 3, '');
                if (method_exists($record, $method)) {
                    $data[(string) $pdfField] = $record->$method($fieldName, $argument);
                }
                continue;
            }

            if ($record->hasField($source)) {
                $data[(string) $pdfField] = $record->getField($source);
            }
        }

        return $data;
    }
}
