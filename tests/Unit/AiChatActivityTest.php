<?php

namespace Tests\Unit;

use App\Support\AiChatActivity;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiChatActivityTest extends TestCase
{
    #[Test]
    public function it_rewrites_absolute_markdown_links_to_app_paths(): void
    {
        $text = 'Created. [Open "Report"](http://example.com/field-reports/12/edit)';
        $sanitized = AiChatActivity::sanitizeText($text);

        $this->assertSame(
            'Created. [Open "Report"](/field-reports/12/edit)',
            $sanitized,
        );
    }

    #[Test]
    public function it_summarizes_delete_steps_without_llm(): void
    {
        $summary = AiChatActivity::summarize('DeleteRecord', [
            'resource' => 'field_reports',
            'id' => 9,
        ]);

        $this->assertSame('Deleting field_reports #9', $summary);
    }
}
