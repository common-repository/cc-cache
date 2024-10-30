<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<p><?= $message; ?> <code><?= $file; ?></code></p>
	<pre><code># BEGIN <?= $marker; ?><br /><?= $rules; ?><br /># END <?= $marker; ?></code></pre>
</div>