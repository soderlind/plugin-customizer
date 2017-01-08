<body class="single " >
	<style>
	body {
	  width: 100%;
	  height: 100%;
	  background-color: #333333;
	  display: flex;
	  align-items: center;
	  justify-content: center;
	}
	#info {
	    width: 90%;
	    height: 90%;
	    background-color: #EEE;
		padding: 1em;
	}
	</style>
	<div id="info">
		<div id="newsletter-content" class="entry-content content">
			<?php echo get_option( 'newsletter_content' ); ?>
		</div>
	</div>
</body>
