<div class="smartcat-connector wrap">
	<h1 class="wp-heading-inline"><?php echo esc_html( $GLOBALS['title'] ); ?></h1>

    <hr class="wp-header-end">
	<?php if ( isset( $_GET['settings-updated'] ) ) { ?>
		<div id=”message” class="updated settings-error notice is-dismissible">
			<p><strong><?php echo __( 'Settings saved.' ) ?></strong></p>
		</div>
	<?php } ?>

    <form method="post" action="options.php">
        <?php wp_nonce_field( 'update-options' ); ?>
        <table class="form-table">

            <?php
                settings_fields( 'smartcat' );
				do_settings_sections( 'smartcat' );
				?>
        </table>

        <p class="submit">
            <input type="submit" class="button-primary" value="<?php echo __( 'Save Changes' ) ?>"/>
        </p>
        
		</form>
</div>