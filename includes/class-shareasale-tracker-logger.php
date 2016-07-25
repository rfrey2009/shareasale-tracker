<?php
class ShareASale_Tracker_Logger {

	public function __construct() {
		return $this;
	}

	public function log_to_db( array $log ) {
		//for now, use generic database insertion from Keeper class method
		//logger's database write logic can always be updated here to something more specific later if needed
		$result = Keeper::toDb( $log, LOGS_TABLE );
		return $result;
	}

	public function log_to_file( $filename, $data ) {
		$fp = fopen( LOGS_LOCATION . $filename, 'a' );
		$written = fwrite( $fp, $data . "\r\n\r\n" );
		fclose( $fp );
		return ( false !== $written ? true : false );
	}
}

