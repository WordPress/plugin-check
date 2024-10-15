<img src="https://example.com/image.jpeg" />

<?php

?>img src="https://example.com/image.jpeg" /><?php

echo '<img src="' . SOMEIMAGE . '">';

$double_quoted = "<img src=\"{$img}\">";

$double_quoted = "<img src='{$img}'>";

$content = <<<'EOD'
<img src="https://example.com/image.jpeg">
EOD;

// Test multi-line text string.
echo '<img
	src="' . $img . '"/>';

// Test multi-line text string with multiple issues.
echo '<img
	src="https://example.com/image.jpeg"/>
	<img src="https://example.com/image.jpeg"/>
	<img
	src="https://example.com/image.jpeg"/>
	';
