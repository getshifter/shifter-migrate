<?php

class Predic_Simple_Backup_Admin {

    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->plugin_public_name = esc_html_x( 'Very Simple Backup', 'plugin public name and admin menu page name', 'predic-simple-backup' );
        $this->plugin_admin_page = $this->plugin_name . '-page';

	}

    /**
	 * Add admin menu page
	 *
	 * @since    1.0.0
	 */
    public function add_menu_page() {
        add_menu_page(
            $this->plugin_public_name . esc_html__( 'Options', 'predic-simple-backup' ), //  The text to be displayed in the title tags of the page when the menu is selected
            $this->plugin_public_name, // The text to be used for the menu
            'manage_options', // The capability required for this menu to be displayed to the user
            $this->plugin_admin_page, //  The slug name to refer to this menu by (should be unique for this menu)
            array( $this, 'render_admin_page' ), // The function to be called to output the content for this page
            'dashicons-index-card', // Menu icon - https://developer.wordpress.org/resource/dashicons/
            79 // Position
        );
    }

    /**
	 * Render admin menu page html
	 *
	 * @since    1.0.0
	 */
    public function render_admin_page() {

        if ( !current_user_can( 'manage_options' ) )  {
            wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
        }
        ?>

        <div class="psb-admin-page wrap">
            <h1><?php echo sprintf( esc_html__( '%s Settings', 'predic-simple-backup' ), $this->plugin_public_name ); ?></h1>

           <div class="psb-admin-page-content">
               <p><?php echo esc_html__( 'This plugin is for small sites that do not need fancy WP plugins for backup jobs. It zip all files from Your WP directory and add database dump into zip.', 'predic-simple-backup' ) ?></p>
               <p><?php echo esc_html__( 'When You click "Backup now" button, please wait untill the proccess is done. Do not navigate away from the page, as this proccess can take long time depending from Your server', 'predic-simple-backup' ) ?></p>
           </div>

            <div id="psb-admin-page-form">
                <form action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
                    <input type="hidden" name="action" value="start_predic_simple_backup">
                    <input type="submit" value="<?php echo esc_html__( 'Backup now', 'predic-simple-backup' ); ?>">
                </form>
            </div>
        </div>

        <?php
        // List all backed files with delete option
        $this->list_backup_files();
    }

    /**
	 * Return backup directory (where backups are stored) path or url
	 *
	 * @since    1.0.0
     * @param   string   $parth_or_url   What to return "dir" for directory, "url" for url
     *
     * @return  string   Return backup directory path or url. No tailing slash at the end
	 */
    private function get_backup_directory( $parth_or_url = 'dir' ) {

        // Name of the backup directory
        $dir_name = 'shifter-migrate';

        // Define files and folders
        $upload_dir = wp_upload_dir();
        $uploads_basedir = $upload_dir['basedir']; // Uploads basedir without slash
        $uploads_basedurl = $upload_dir['baseurl']; // Uploads basedir without slash

        // Make directory in uploads folder to store backups, if don't exist
        $backup_files_dir = $uploads_basedir . '/' . $dir_name;

        if ( ! file_exists(realpath($backup_files_dir) ) ) {
            if ( ! mkdir($backup_files_dir, 0755) ) {
                wp_die( esc_html__( 'Can not create parent directory to store backup files' ) );
            }
        }

        if ( $parth_or_url === 'dir' ) {
            return $backup_files_dir;
        } else {
            return $uploads_basedurl . '/' . $dir_name;
        }


    }

    /**
	 * Recursively Backup Files & Folders to ZIP-File and store it to backup directory. Redirect to homepage after that
	 *
	 * @since    1.0.0
	 */
    public function make_site_backup() {

        // Prevent executing on ajax calls
        if ( defined( 'DOING_AJAX' ) ) {
            return false;
        }

        // Define files and folders
        $backup_files_dir = $this->get_backup_directory();
        if ( ! $backup_files_dir ) {
            // Directory in uploads folder could not be created
            $this->redirect_to_admin_page( esc_html__( 'Directory to store backup files does not exist', 'predic-simple-backup' ), 'notice-error' );
        }

        // Folder to backup and zip name and path
        $zip_name = strtolower( sanitize_file_name( get_bloginfo( 'name' ) ) . date("Y-m-d-h-i-sa") ) .".zip";
        $destination = $backup_files_dir . '/' . $zip_name; // Destination dir and filename
        $directory = ABSPATH; // The folder which you archivate

        // Make sure the script can handle large folders/files
        $this->bypass_server_limit();

        // Start the backup!
        if ( $this->zipData( $directory, $destination ) ) {

            $this->redirect_to_admin_page( esc_html__( 'Backup archive successfully created', 'predic-simple-backup' ), 'notice-success' );

        } else {

            $this->redirect_to_admin_page( esc_html__( 'Something went wrong. Backup archive could not be created', 'predic-simple-backup' ), 'notice-error' );

        }

    }

    /**
	 * Create ZIP backup file
	 *
	 * @since    1.0.0
     * @param   string   $directory   Path of the directory to backup
     * @param   string   $destination   Path of the destination directory
     *
     * @return   boolean   True or false
	 */
    private function zipData( $directory, $destination ) {

        // Prevent executing on ajax calls
        if ( defined( 'DOING_AJAX' ) ) {
            return false;
        }

        $upload_dir = wp_upload_dir();
        // Uploads basedir without slash
        $uploads_basedir = $upload_dir['basedir'];
        // Set database name
        $database_add_to_root = 'wp.sql';
        $database_filename = $uploads_basedir . '/' . $database_add_to_root;

        if ( extension_loaded( 'zip' ) ) {

            if ( file_exists( $directory ) ) {

                $zip = new ZipArchive();

                if ( $zip->open( $destination, ZIPARCHIVE::CREATE ) ) {

                        // Create webroot inside Zip
                        $zip->addEmptyDir('webroot');

                        $directory = realpath( $directory );

                        if ( is_dir( $directory ) ) {

                            $iterator = new RecursiveDirectoryIterator( $directory );
                            // skip dot files while iterating
                            $iterator->setFlags( RecursiveDirectoryIterator::SKIP_DOTS );
                            $files = new RecursiveIteratorIterator( $iterator, RecursiveIteratorIterator::SELF_FIRST );

                            foreach ( $files as $file ) {
                                $file = realpath( $file );
                                if ( is_dir( $file ) ) {
                                        $zip->addEmptyDir( str_replace( $directory . '/', '', $file . '/' ) );
                                } else if (is_file($file)) {
                                        $zip->addFromString( str_replace($directory . '/', '', $file ), file_get_contents( $file ) );
                                }
                            }

                        } else if ( is_file( $directory ) ) {
                                $zip->addFromString( basename( $directory ) . '', file_get_contents( $directory ) );
                        }

						/*
						 * Get database dump
						 */
						if ( is_callable('shell_exec') && false === stripos(ini_get('disable_functions'), 'shell_exec') ) {

							// Try to export database and add it to the zip if exec function allowed on server
							try {

								exec('mysqldump --add-drop-table --user=' . DB_USER . ' --password=' . DB_PASSWORD . ' --host=' . DB_HOST . ' ' . DB_NAME . ' > ' . $database_filename);

								// If database dump file created
								if ( file_exists( $database_filename ) ) {

									$database_file_content = file_get_contents( $database_filename );

									// If error while dumping file will be empty
									if ( empty( $database_file_content ) ) {

										unlink( $database_filename );
										$zip->close();
										$this->redirect_to_admin_page( esc_html__( 'Database backup file could not be exported', 'predic-simple-backup' ), 'notice-warning' );

									}

									// Add database file to zip and delete created file
									$zip->addFromString( str_replace( $directory . '/', '', $database_add_to_root ), $database_file_content );
									unlink( $database_filename );

								} else {

									$zip->close();
									$this->redirect_to_admin_page( esc_html__( 'Database backup file could not be created', 'predic-simple-backup' ), 'notice-warning' );
								}

							} catch(Exception $e) {

								$zip->close();
								$this->redirect_to_admin_page( $e->getMessage(), 'notice-error' );

							}

						} else {

							/*
							 * Use fallback method if exec function not allowed on server
							 */
							$db_dump = $this->dump_database();

							if ( !empty( $db_dump ) ) {
								$zip->addFromString( str_replace( $directory . '/', '', $database_add_to_root ), $db_dump );
							} else {
								$zip->close();
								chmod( $destination, 0644 );
								$this->redirect_to_admin_page( esc_html__( 'Database backup file could not be created', 'predic-simple-backup' ), 'notice-warning' );
							}

						}

                }

                if ( $zip->close() ) {
                    // Set mode for newly created archive file
                    chmod( $destination, 0644 );
                    return true;
                } else {
                    return false;
                }

            }

        }

        return false;

    }

	/**
	 * Return database dump when exec function not allowed on server
	 *
	 * @since 1.1.0
	 * @global  type  $wpdb  Using the $wpdb Object
	 * @return  string  database dump without exec function
	 */
	private function dump_database() {

		global $wpdb;

		/* Start dump */

		$return = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;

		//get all of the tables

		$tables = array();

		// Get all tables
		$result = $wpdb->get_results( 'SHOW TABLES', ARRAY_N );

		//Store all tables in array
		foreach ( $result as $key => $value ) {

			if ( isset( $value[0] ) ) {
				$tables[] = $value[0];
			} else {
				continue;
			}

		}


		// Get create table and insert dump for each table
		foreach( $tables as $table ) {

			$table = '`' . $table . '`';

			// Get all rows
			$result = $wpdb->get_results( 'SELECT * FROM '.$table, ARRAY_A );

			// Add drop statement
			$return.= 'DROP TABLE IF EXISTS '.$table.';';

			// Get create table statement
			$create_table = $wpdb->get_results('SHOW CREATE TABLE '.$table, ARRAY_N);
			$return .= isset( $create_table[0][1] ) ? PHP_EOL . PHP_EOL .$create_table[0][1].";" . PHP_EOL . PHP_EOL . PHP_EOL : "";

			// If no rows than continue
			if ( count($result) < 1 ) {
				continue;
			}

			// If rows get all results and prepare insert statement
			$columns = array();
			$column_values = array();

			// Get all columns names for insert statement
			foreach ( $result[0] as $column => $value ) {
				// Add backticks for each column
				$columns[] = '`' . $column . '`';
			}

			/* Start insert statement */
			$return.= "INSERT INTO " . $table . " (" . implode( ', ', $columns ) . ") VALUES";

			// Set values to store
			$count_values = count($result);
			foreach ( $result as $key => $row_values ) {

				// Escape all values in temporary array
				$tmp_array = array();
				foreach ( $row_values as $add_single_quotes ) {
					$tmp_array[] = "'" . str_replace( array( PHP_EOL, "'" ), array( '\n', "''" ), $add_single_quotes ) . "'";
				}

				// Add values to insert statement for each row
				$return .= PHP_EOL;
				$return .= "(" . implode( ',', $tmp_array ) . ")";

				// add comma if not last key
				if ( ( $count_values - 1 ) !== $key ) {
					$return .= ',';
				} else {
					// Close statement
					$return .= ';';
				}

			}

			// Add empty space
			$return.= PHP_EOL . PHP_EOL . PHP_EOL;

		}

		return $return;
	}

    /**
	 * List all backup files under the backup folder for the user to download or delete
	 *
	 * @since    1.0.0
	 */
    private function list_backup_files() {

        // Get backup directory
        $backup_dir = $this->get_backup_directory();
        $backup_dir_url = $this->get_backup_directory( 'url' );

        $files = scandir( $backup_dir );

        echo '<h3>' . esc_html__( 'List of backups', 'predic-simple-backup' ) . '</h3>';

        // Make list of backup files
        echo '<ul>';

        foreach ( $files as $file ) {

            if ( $file === '.' || $file === '..' ) {
                continue;
            }

            $filename = $backup_dir . '/' . $file;

            echo '<li>'
                    . $file . ' ' . round( filesize( $filename ) / ( 1024 * 1024 ), 2 ) . ' MB '
                    . '<a href="' . esc_url( $backup_dir_url . '/' . $file ) . '">' . esc_html__( 'Download', 'predic-simple-backup' ) . '</a> '
                    . '<a class="ptb-delete-backup" href="' . esc_url( esc_url( admin_url('admin-post.php') ) . '?action=delete_predic_simple_backup&psb_delete_file=' . $file ) . '">' . esc_html__( 'Delete', 'predic-simple-backup' ) . '</a>'
                . '</li>';
        }

        echo '</ul>';

    }

    /**
	 * Delete backup file from backup directory
	 *
	 * @since    1.0.0
	 */
    public function delete_backup_file() {

        // Prevent executing on ajax calls
        if ( defined( 'DOING_AJAX' ) ) {
            return false;
        }

        // Get file to delete
        $file = isset( $_GET['psb_delete_file'] ) && !empty( $_GET['psb_delete_file'] ) ? sanitize_text_field( $_GET['psb_delete_file'] ) : NULL;

        if ( empty( $file ) ) {
            $this->redirect_to_admin_page( esc_html__( 'Please select file to delete', 'predic-simple-backup' ), 'notice-warning' );
        }

        $backup_dir = $this->get_backup_directory();
        $filename = $backup_dir . '/' . $file;

        if ( file_exists( $filename ) ) {

            // Delete the file
            unlink( $filename );

            // redirect to plugin page
            $this->redirect_to_admin_page( esc_html__( 'File successfully deleted', 'predic-simple-backup' ), 'notice-success' );
        }

    }

    /**
	 * Manage redirects and messages. Function check_and_add_admin_notices check for GET params and adds admin notices
	 *
	 * @since    1.0.0
     * @param   string   $message   Message to add as $_GET param
     * @param   string   $class   Css class to add as $_GET param. Can use these classes: notice-error, notice-warning, notice-success, or notice-info
	 */
    private function redirect_to_admin_page( $admin_notice = NULL, $class = NULL  ) {

        $url = add_query_arg( array(
            'page' => $this->plugin_admin_page,
            'ptb_message' => !empty( $admin_notice ) ? urlencode( $admin_notice ) : '',
            'ptb_message_class' => !empty( $class ) ? urlencode( $class ) : ''
        ), admin_url( 'admin.php' ) );

        wp_redirect( $url );
        die();
    }

    /**
	 * Bypass limit server if possible
	 *
	 * @since    1.0.0
	 */
    private function bypass_server_limit() {
        @ini_set('memory_limit','1024M');
        @ini_set('max_execution_time','0');
    }

    /**
	 * Add admin notice
	 *
	 * @since    1.0.0
     * @param   string   $message   Message for admin notice
     * @param   string   $class   Css class for admin notice
     *
	 */
    public function add_admin_notice( $message, $class = 'notice-info' ) {

        // Add admin notice
        add_action( 'admin_notices', function () use ( $message, $class ) {
            ?>
            <div class="notice <?php echo esc_attr( $class ) ?> is-dismissible">
                <p><?php echo esc_html( $message ); ?></p>
            </div>
            <?php
        } );

    }

    /*
     * Display message defined in $_GET['ptb_message'] as admin notice, also set admin notice class
     *
     * @since 1.0.0
     */
    public function check_and_add_admin_notices() {

        if ( isset( $_GET['ptb_message'] ) && !empty( $_GET['ptb_message'] ) ) {

            $class = isset( $_GET['ptb_message_class'] ) && !empty( $_GET['ptb_message_class'] ) ? $_GET['ptb_message_class'] : '';

            $this->add_admin_notice( $_GET['ptb_message'], $class );
        }

    }

}
