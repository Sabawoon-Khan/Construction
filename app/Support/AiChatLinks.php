<?php

namespace App\Support;

use App\Models\FieldReport;
use Illuminate\Database\Eloquent\Model;

/**
 * Markdown links for General Chat tool results (rendered as clickable Inertia links in the panel).
 */
class AiChatLinks
{
    public static function markdown(string $label, string $url): string
    {
        $safeLabel = str_replace(['[', ']', '(', ')'], ['(', ')', '', ''], $label);

        return "[{$safeLabel}]({$url})";
    }

    public static function fieldReport(FieldReport $report): string
    {
        return self::markdown(
            'Open "'.$report->title.'"',
            route('field-reports.edit', $report, absolute: false),
        );
    }

    public static function fieldReportLine(FieldReport $report): string
    {
        $parts = [
            '#'.$report->id,
            '"'.$report->title.'"',
            'status: '.$report->status,
        ];

        if (filled($report->location)) {
            $parts[] = 'location: '.$report->location;
        }

        return implode(' · ', $parts).' — '.self::fieldReport($report);
    }

    /**
     * @param  class-string<Model>  $modelClass
     */
    public static function forModel(Model $model, string $label, string $routeName, mixed $routeParam = null): string
    {
        $param = $routeParam ?? $model;

        return self::markdown($label, route($routeName, $param, absolute: false));
    }
}
