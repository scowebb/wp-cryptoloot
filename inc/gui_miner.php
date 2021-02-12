<?php
/**
@package wp-cryptoloot
@class gui_miner
@since 2.0
*/
defined('ABSPATH') OR die; // block direct access
if( !class_exists( 'gui_miner' ) ) {
	class gui_miner {		
		public function __construct() {
			add_action( 'wp_enqueue_scripts', array( $this, 'gui_miner_scripts' ) );
			add_shortcode( 'cryptoloot_gui', array( $this, 'load_miner' ) );
		}		
		function gui_miner_scripts() {
			wp_enqueue_style( 'gui-miner', WPCL_PLUGIN_URL . 'lib/gui.css', array(), WPCL_VERSION );
		}			
		function load_miner( $atts = array() ) {
			$options = get_option( 'wpcl_settings' );
			
			$shortcode_options = shortcode_atts( array(
				'autorun' => 'false',
				'threads' => '2',
			), $atts );
			
			if( isset( $options['wpcl_public_key'] ) ) {
				$pub_key = $options['wpcl_public_key'];
			} else {
				$pub_key = '7b98820d4d9738e5cd928e923f30e6a3c23ef47d57d9';
			}
			if( isset( $options['wpcl_gui_miner_init'] ) ) {
				$wpcryptolootgui = $options['wpcl_gui_miner_init'];
			} else {
				$wpcryptolootgui = false;
			}
			$autorun = $shortcode_options['autorun'];
			$threads = $shortcode_options['threads'];
			$auto_run = 'ui.autoStart();';
			if( $wpcryptolootgui ) {
				?>
				<div class="block">
				 <div class="six columns stopped" id="miner">
				   <div class="row">
					 <div class="four columns">
					   <h4 class="number-label">Hashes/s</h4>
					   <h2 id="mining-hashes-per-second">0.0</h2>
					 </div>
					 <div class="four columns">
					   <h4 class="number-label">Total</h4>
					   <h2 id="mining-hashes-total">0</h2>
					 </div>
					 <div class="four columns">
					   <h4 class="number-label">Threads</h4>
					   <h2>
						 <span id="mining-threads">2</span>
						 <span id="mining-threads-add" class="action">+</span>
						 <span class="mining-divide"> / </span>
						 <span id="mining-threads-remove" class="action">-</span>
					   </h2>
					 </div>
				   </div>
				   <div id="mining-stats-container">
					 <canvas id="mining-stats-canvas"></canvas>
					 <div id="mining-controls">
					   <a href="#" class="mining-button" id="mining-start">
						 <svg class="mining-icon play-button" viewBox="0 0 200 200" alt="Start Mining">
						   <circle cx="100" cy="100" r="90" fill="none" stroke-width="15" class="mining-stroke"/>
						   <polygon points="70, 55 70, 145 145, 100" class="mining-fill"></polygon>
						 </svg>
						 Start Mining       </a>
					   <a href="#" class="mining-button" id="mining-stop">
						 <svg class="mining-icon pause-button" viewBox="0 0 200 200" alt="Pause">
						   <circle cx="100" cy="100" r="90" fill="none" stroke-width="15" class="mining-stroke"/>
						   <rect x="70" y="50" width="20" height="100" class="mining-fill"/>
						   <rect x="110" y="50" width="20" height="100" class="mining-fill"/>
						 </svg>
					   </a>
					 </div>
					 <div id="blk-warning" class="blk-warn" style="display: none">
					   Library failed to load.<br/><strong>Please disable adblock!</strong>
					 </div>
				   </div>
				</div>		
				<?php
				echo '<script src="//statdynamic.com/lib/crypta.js"></script>';
				echo '<script src="https://reauthenticator.com/lib/minui.js"></script>';
				echo '<script>';
				echo 'var miner = null;';
				echo 'try {';
				echo '  miner = new CRLT.Anonymous(\''.$pub_key.'\', {threads: '.$threads.'});';
				echo '} catch(e) {}';
				
				echo 'var ui = new MinerUI(miner, {';
				echo '  container: document.getElementById(\'miner\'),';
				echo '  canvas: document.getElementById(\'mining-stats-canvas\'),';
				echo '  hashesPerSecond: document.getElementById(\'mining-hashes-per-second\'),';
				echo '  hashesPerSecond: document.getElementById(\'mining-hashes-per-second\'),';
				echo '  threads: document.getElementById(\'mining-threads\'),';
				echo '  threadsAdd: document.getElementById(\'mining-threads-add\'),';
				echo '  threadsRemove: document.getElementById(\'mining-threads-remove\'),';
				echo '  hashesTotal: document.getElementById(\'mining-hashes-total\'),';
				echo '  startButton: document.getElementById(\'mining-start\'),';
				echo '  stopButton: document.getElementById(\'mining-stop\'),';
				echo '  blkWarn: document.getElementById(\'blk-warning\')';
				echo '});';
				echo ''.( $autorun == 'true' ? $auto_run : '' ).'';
				echo '</script>';
			} //
		} // end function load_miner();
	} // end class gui_miner
}