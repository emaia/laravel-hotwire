<?php

use Emaia\LaravelHotwire\Support\RichTextContent;
use Illuminate\Contracts\Support\Htmlable;

it('recognizes semantically blank rich text', function (string $html) {
    expect(RichTextContent::fromHtml($html)->isBlank())->toBeTrue();
})->with([
    'empty string' => '',
    'whitespace' => " \t\r\n ",
    'empty paragraph' => '<p></p>',
    'line break placeholder' => '<p><br></p>',
    'non-breaking spaces' => '<div>&nbsp;&#160;</div>',
    'html5 whitespace entities' => '<p>&Tab;&NewLine;&ZeroWidthSpace;&NoBreak;</p>',
    'html5 invisible operator entities' => '<p>&ApplyFunction;&af;&InvisibleTimes;&it;&InvisibleComma;&ic;</p>',
    'nested empty elements' => '<blockquote><p><strong></strong></p></blockquote>',
    'editor artifacts' => "<p>\u{200B}\u{FEFF}\u{2060}\u{00AD}</p>",
    'unicode next line' => "<p>\u{0085}</p>",
    'standalone joiners' => "<p>\u{200C}\u{200D}</p>",
    'standalone default ignorables' => "<p>\u{034F}\u{FE0F}</p>",
    'empty table' => '<table><tbody><tr><td><p></p></td></tr></tbody></table>',
    'unknown atom wrapper' => '<div data-type="widget"></div>',
    'support elements' => '<source src="video.mp4"><track src="captions.vtt"><input value="metadata">',
]);

it('keeps zero as non-blank content', function () {
    $content = RichTextContent::fromHtml('<p>0</p>');

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('0');
});

it('recognizes standard media and embed nodes as non-text content', function (string $html) {
    $content = RichTextContent::fromHtml($html);

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('')
        ->and($content->plainTextLength())->toBe(0);
})->with([
    'image' => '<p><img src="/storage/photo.png" alt="Team photo"></p>',
    'horizontal rule' => '<p></p><hr><p></p>',
    'youtube' => '<div data-youtube-video><iframe src="https://www.youtube.com/embed/x"></iframe></div>',
    'audio' => '<audio src="podcast.mp3"></audio>',
    'video' => '<video src="movie.mp4"></video>',
    'iframe' => '<iframe src="https://example.com/embed"></iframe>',
    'svg' => '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"></path></svg>',
    'embed' => '<embed src="document.pdf">',
    'object' => '<object data="document.pdf"></object>',
    'canvas' => '<canvas data-chart="sales"></canvas>',
]);

it('ignores non-text content nested inside ignored elements', function (string $html) {
    expect(RichTextContent::fromHtml($html)->isBlank())->toBeTrue();
})->with([
    'template' => '<template><img src="draft.png"></template>',
]);

it('recognizes iframe content without counting its fallback text', function () {
    $content = RichTextContent::fromHtml('<iframe>Fallback<img src="draft.png"></iframe>');

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('')
        ->and($content->plainTextLength())->toBe(0);
});

it('does not count fallback text inside opaque media', function (string $html) {
    $content = RichTextContent::fromHtml($html);

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('')
        ->and($content->plainTextLength())->toBe(0);
})->with([
    'audio' => '<audio src="podcast.mp3">Your browser does not support audio.</audio>',
    'canvas' => '<canvas>Chart description for screen readers</canvas>',
    'object' => '<object data="document.pdf">Download the PDF instead</object>',
    'video' => '<video src="movie.mp4">Your browser does not support video.</video>',
]);

it('keeps captions outside opaque media as visible text', function () {
    $content = RichTextContent::fromHtml(
        '<figure><video src="movie.mp4">Fallback</video><figcaption>Visible caption</figcaption></figure>',
    );

    expect($content->plainText())->toBe('Visible caption')
        ->and($content->plainTextLength())->toBe(mb_strlen('Visible caption'));
});

it('counts visible svg text but ignores svg metadata', function () {
    $content = RichTextContent::fromHtml(
        '<svg><title>Logo title</title><desc>Long description</desc><text>Hello</text></svg>',
    );

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('Hello')
        ->and($content->plainTextLength())->toBe(5);
});

it('extracts decoded inline text without adding spaces around formatting', function () {
    $content = RichTextContent::fromHtml(
        '<p><strong>Rock</strong><em>&amp;</em><span>Roll</span> &lt;live&gt;</p>',
    );

    expect($content->plainText())->toBe('Rock&Roll <live>');
});

it('does not decode escaped html5 entity text twice', function () {
    $content = RichTextContent::fromHtml('<p>&amp;ZeroWidthSpace;</p>');

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe('&ZeroWidthSpace;');
});

it('keeps named entities inside attributes from changing the document structure', function () {
    $content = RichTextContent::fromHtml('<p title="&quot;quoted&quot;">Visible</p>');

    expect($content->plainText())->toBe('Visible');
});

it('decodes visible html5 entities once before measuring text', function () {
    $content = RichTextContent::fromHtml('<p>&CounterClockwiseContourIntegral;</p>');

    expect($content->plainText())->toBe('∳')
        ->and($content->plainTextLength())->toBe(1);
});

it('normalizes block boundaries line breaks and whitespace', function () {
    $content = RichTextContent::fromHtml(<<<'HTML'
        <h2>  Release   notes </h2>
        <p>Hello <strong>rich</strong> text<br>Second line</p>
        <ul><li>First item</li><li>Second item</li></ul>
        <blockquote>Finished</blockquote>
        HTML);

    expect($content->plainText())->toBe(
        "Release notes\nHello rich text\nSecond line\nFirst item\nSecond item\nFinished",
    );
});

it('ignores non-content elements and comments', function () {
    $content = RichTextContent::fromHtml(<<<'HTML'
        <p>Visible</p>
        <!-- comment text -->
        <script>script text</script>
        <style>.hidden { content: "style text"; }</style>
        <template>template text</template>
        <noscript>noscript text</noscript>
        <p>Content</p>
        HTML);

    expect($content->plainText())->toBe("Visible\nContent");
});

it('recovers ordinary malformed html fragments', function () {
    $content = RichTextContent::fromHtml('<p>First<strong> bold<p>Second');

    expect($content->plainText())->toBe("First bold\nSecond");
});

it('does not lose content placed beyond malformed document boundaries', function () {
    $content = RichTextContent::fromHtml('</body></html><p>Visible</p>');

    expect($content->plainText())->toBe('Visible');
});

it('separates semantic block elements', function () {
    $content = RichTextContent::fromHtml(
        '<form>Form</form><fieldset>Fields</fieldset><details><summary>Summary</summary>Details</details><dialog>Dialog</dialog>',
    );

    expect($content->plainText())->toBe("Form\nFields\nSummary\nDetails\nDialog");
});

it('counts the normalized text with mb string semantics', function () {
    $content = RichTextContent::fromHtml('<p>ação</p><p>漢字</p>');

    expect($content->plainText())->toBe("ação\n漢字")
        ->and($content->plainTextLength())->toBe(mb_strlen("ação\n漢字"));
});

it('canonicalizes exclusively invisible content to an empty string', function () {
    $content = RichTextContent::fromHtml("<p>\u{200C}\u{200D}</p>");

    expect($content->isBlank())->toBeTrue()
        ->and($content->plainText())->toBe('')
        ->and($content->plainTextLength())->toBe(0);
});

it('preserves joiners when they participate in visible text', function () {
    $emoji = '👨‍👩‍👧️';
    $content = RichTextContent::fromHtml("<p>{$emoji}</p>");

    expect($content->isBlank())->toBeFalse()
        ->and($content->plainText())->toBe($emoji)
        ->and($content->plainTextLength())->toBe(mb_strlen($emoji));
});

it('rejects invalid utf-8', function () {
    RichTextContent::fromHtml("invalid\xFF");
})->throws(InvalidArgumentException::class, 'Rich text HTML must be valid UTF-8.');

it('cannot be mistaken for safe renderable html', function () {
    $content = RichTextContent::fromHtml('<p>Content</p>');

    expect($content)->not->toBeInstanceOf(Htmlable::class)
        ->and($content)->not->toBeInstanceOf(Stringable::class);
});
