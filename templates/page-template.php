<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="wrap">
	<form method="post" action="options.php">
		<?php
			settings_fields( 'cache' );
			do_settings_sections( 'cache' );
			submit_button();
		?>
	</form>
</div>