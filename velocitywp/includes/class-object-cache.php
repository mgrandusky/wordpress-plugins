<?php
/**
 * Object Cache Class
 *
 * Redis/Memcached/APCu integration
 *
 * @package VelocityWP
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * VelocityWP_Object_Cache class
 */
class VelocityWP_Object_Cache {

	/**
	 * Cache backend
	 *
	 * @var string
	 */
	private $backend = '';

	/**
	 * Cache status
	 *
	 * @var bool
	 */
	private $is_active = false;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_ajax_velocitywp_test_object_cache', array( $this, 'ajax_test_cache' ) );
		add_action( 'wp_ajax_velocitywp_flush_object_cache', array( $this, 'ajax_flush_cache' ) );
		add_action( 'wp_ajax_velocitywp_install_dropin', array( $this, 'ajax_install_dropin' ) );
		add_action( 'wp_ajax_velocitywp_remove_dropin', array( $this, 'ajax_remove_dropin' ) );
		add_action( 'wp_ajax_velocitywp_get_cache_stats', array( $this, 'ajax_get_stats' ) );
	}

	/**
	 * Initialize object cache
	 */
	public function init() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['object_cache'] ) ) {
			return;
		}

		$this->detect_backend();
		$this->is_active = $this->is_cache_active();

		// Add admin notices
		if ( is_admin() ) {
			add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		}
	}

	/**
	 * Detect available cache backend
	 *
	 * @return string Detected backend type
	 */
	public function detect_cache_type() {
		$options = get_option( 'velocitywp_options', array() );
		$preferred = ! empty( $options['object_cache_backend'] ) ? $options['object_cache_backend'] : 'auto';

		if ( $preferred === 'auto' ) {
			// Auto-detect available backend
			if ( class_exists( 'Redis' ) && $this->test_redis() ) {
				$this->backend = 'redis';
			} elseif ( class_exists( 'Memcached' ) && $this->test_memcached() ) {
				$this->backend = 'memcached';
			} elseif ( function_exists( 'apcu_fetch' ) && $this->test_apcu() ) {
				$this->backend = 'apcu';
			}
		} else {
			$this->backend = $preferred;
		}

		return $this->backend;
	}

	/**
	 * Detect available cache backend (alias for detect_cache_type)
	 */
	private function detect_backend() {
		return $this->detect_cache_type();
	}

	/**
	 * Test Redis connection
	 *
	 * @param string $host     Redis host (optional, uses option if not provided).
	 * @param int    $port     Redis port (optional, uses option if not provided).
	 * @param string $password Redis password (optional, uses option if not provided).
	 * @param int    $database Database number 0-15 (optional, uses option if not provided).
	 * @return bool Whether Redis is available.
	 */
	public function test_redis_connection( $host = '', $port = 0, $password = '', $database = 0 ) {
		if ( ! class_exists( 'Redis' ) ) {
			return false;
		}

		try {
			$redis = new Redis();
			$options = get_option( 'velocitywp_options', array() );
			
			// Use provided params or fall back to options
			$host = ! empty( $host ) ? $host : ( ! empty( $options['redis_host'] ) ? $options['redis_host'] : '127.0.0.1' );
			$port = ! empty( $port ) ? intval( $port ) : ( ! empty( $options['redis_port'] ) ? intval( $options['redis_port'] ) : 6379 );
			$password = ! empty( $password ) ? $password : ( ! empty( $options['redis_password'] ) ? $options['redis_password'] : '' );
			$database = ! empty( $database ) ? intval( $database ) : ( ! empty( $options['redis_database'] ) ? intval( $options['redis_database'] ) : 0 );
			
			$connected = $redis->connect( $host, $port, 1 );
			
			if ( $connected && ! empty( $password ) ) {
				$redis->auth( $password );
			}

			if ( $connected && $database > 0 && $database <= 15 ) {
				$redis->select( $database );
			}

			if ( $connected ) {
				$redis->ping();
				$redis->close();
				return true;
			}
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Test Redis connection (private method for internal use)
	 *
	 * @return bool Whether Redis is available.
	 */
	private function test_redis() {
		return $this->test_redis_connection();
	}

	/**
	 * Test Memcached connection
	 *
	 * @param array $servers Array of servers in format ['host:port', 'host:port'] (optional).
	 * @return bool Whether Memcached is available.
	 */
	public function test_memcached_connection( $servers = array() ) {
		if ( ! class_exists( 'Memcached' ) ) {
			return false;
		}

		try {
			$memcached = new Memcached();
			$options = get_option( 'velocitywp_options', array() );
			
			// If servers provided, use them, otherwise use options
			if ( empty( $servers ) ) {
				if ( ! empty( $options['memcached_servers'] ) ) {
					// Parse servers from textarea (one per line)
					$servers = array_filter( array_map( 'trim', explode( "\n", $options['memcached_servers'] ) ) );
				} else {
					// Default server
					$host = ! empty( $options['memcached_host'] ) ? $options['memcached_host'] : '127.0.0.1';
					$port = ! empty( $options['memcached_port'] ) ? intval( $options['memcached_port'] ) : 11211;
					$servers = array( $host . ':' . $port );
				}
			}

			// Add servers to memcached
			foreach ( $servers as $server ) {
				$parts = explode( ':', $server );
				$host = trim( $parts[0] );
				$port = isset( $parts[1] ) ? intval( trim( $parts[1] ) ) : 11211;
				$memcached->addServer( $host, $port );
			}

			// Test connection
			$memcached->set( 'velocitywp_test', 'test', 10 );
			$result = $memcached->get( 'velocitywp_test' );
			
			return $result === 'test';
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Test Memcached connection (private method for internal use)
	 *
	 * @return bool Whether Memcached is available.
	 */
	private function test_memcached() {
		return $this->test_memcached_connection();
	}

	/**
	 * Test APCu availability
	 *
	 * @return bool Whether APCu is available.
	 */
	public function test_apcu() {
		if ( ! function_exists( 'apcu_fetch' ) ) {
			return false;
		}

		try {
			apcu_store( 'velocitywp_test', 'test', 10 );
			$result = apcu_fetch( 'velocitywp_test' );
			return $result === 'test';
		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Check if object cache is active
	 *
	 * @return bool Whether cache is active.
	 */
	public function is_dropin_installed() {
		// Check if object-cache.php drop-in exists
		$object_cache_file = WP_CONTENT_DIR . '/object-cache.php';
		return file_exists( $object_cache_file );
	}

	/**
	 * Check if object cache is active (private method for internal use)
	 *
	 * @return bool Whether cache is active.
	 */
	private function is_cache_active() {
		return $this->is_dropin_installed();
	}

	/**
	 * Generate and install object cache drop-in
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function generate_dropin() {
		if ( empty( $this->backend ) ) {
			$this->detect_cache_type();
			if ( empty( $this->backend ) ) {
				return new WP_Error( 'no_backend', __( 'No cache backend available', 'velocitywp' ) );
			}
		}

		$options = get_option( 'velocitywp_options', array() );
		$dropin_dest = WP_CONTENT_DIR . '/object-cache.php';

		// Generate drop-in content based on backend
		$dropin_content = $this->generate_dropin_content( $this->backend, $options );

		if ( is_wp_error( $dropin_content ) ) {
			return $dropin_content;
		}

		// Backup existing drop-in
		if ( file_exists( $dropin_dest ) ) {
			$backup = $dropin_dest . '.bak.' . time();
			copy( $dropin_dest, $backup );
		}

		// Write drop-in file
		$result = file_put_contents( $dropin_dest, $dropin_content );

		if ( ! $result ) {
			return new WP_Error( 'write_failed', __( 'Failed to write drop-in file', 'velocitywp' ) );
		}

		$this->is_active = true;

		return true;
	}

	/**
	 * Generate drop-in file content for a specific backend
	 *
	 * @param string $backend Backend type (redis, memcached, apcu).
	 * @param array  $options Plugin options.
	 * @return string|WP_Error Drop-in file content or error.
	 */
	private function generate_dropin_content( $backend, $options ) {
		switch ( $backend ) {
			case 'redis':
				return $this->generate_redis_dropin( $options );
			case 'memcached':
				return $this->generate_memcached_dropin( $options );
			case 'apcu':
				return $this->generate_apcu_dropin( $options );
			default:
				return new WP_Error( 'invalid_backend', __( 'Invalid cache backend', 'velocitywp' ) );
		}
	}

	/**
	 * Generate Redis drop-in content
	 *
	 * @param array $options Plugin options.
	 * @return string Drop-in file content.
	 */
	private function generate_redis_dropin( $options ) {
		$host = ! empty( $options['redis_host'] ) ? $options['redis_host'] : '127.0.0.1';
		$port = ! empty( $options['redis_port'] ) ? intval( $options['redis_port'] ) : 6379;
		$password = ! empty( $options['redis_password'] ) ? $options['redis_password'] : '';
		$database = ! empty( $options['redis_database'] ) ? intval( $options['redis_database'] ) : 0;

		$password_code = '';
		if ( ! empty( $password ) ) {
			$password_code = "\$this->redis->auth( '" . addslashes( $password ) . "' );";
		}

		$database_code = '';
		if ( $database > 0 && $database <= 15 ) {
			$database_code = "\$this->redis->select( " . intval( $database ) . " );";
		}

		return $this->get_redis_dropin_template( $host, $port, $password_code, $database_code );
	}

	/**
	 * Get Redis drop-in template
	 *
	 * @param string $host          Redis host.
	 * @param int    $port          Redis port.
	 * @param string $password_code Password authentication code.
	 * @param string $database_code Database selection code.
	 * @return string Template content.
	 */
	private function get_redis_dropin_template( $host, $port, $password_code, $database_code ) {
		$content = <<<'PHP'
<?php
/**
 * Object Cache Drop-in - Redis Backend
 * Generated by VelocityWP
 * 
 * @package VelocityWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds data to the cache, if the cache key doesn't already exist.
 */
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, $expire );
}

/**
 * Closes the cache.
 */
function wp_cache_close() {
	return true;
}

/**
 * Decrements numeric cache item's value.
 */
function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $offset, $group );
}

/**
 * Removes the cache contents matching key and group.
 */
function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

/**
 * Removes all cache items.
 */
function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

/**
 * Retrieves the cache contents from the cache by key and group.
 */
function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

/**
 * Increment numeric cache item's value
 */
function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $offset, $group );
}

/**
 * Sets up Object Cache Global and assigns it.
 */
function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache();
}

/**
 * Replaces the contents of the cache with new data.
 */
function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $data, $group, $expire );
}

/**
 * Saves the data to the cache.
 */
function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $data, $group, $expire );
}

/**
 * Switches the internal blog ID.
 */
function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	return $wp_object_cache->switch_to_blog( $blog_id );
}

/**
 * Adds a group or set of groups to the list of global groups.
 */
function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_global_groups( $groups );
}

/**
 * Adds a group or set of groups to the list of non-persistent groups.
 */
function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_non_persistent_groups( $groups );
}

/**
 * WP Object Cache - Redis Backend
 */
class WP_Object_Cache {
	private $redis;
	private $cache = array();
	private $global_groups = array();
	private $non_persistent_groups = array();
	public $cache_hits = 0;
	public $cache_misses = 0;
	private $blog_prefix;
	private $multisite;

	public function __construct() {
		global $blog_id, $table_prefix;
		
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? $blog_id : $table_prefix;
		
		try {
			$this->redis = new Redis();
			$connected = $this->redis->connect( 'REDIS_HOST', REDIS_PORT, 1 );
			
			if ( $connected ) {
				PASSWORD_CODE
				DATABASE_CODE
			}
		} catch ( Exception $e ) {
			$this->redis = null;
		}
	}

	private function get_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = $this->multisite && ! isset( $this->global_groups[ $group ] ) 
			? $this->blog_prefix . ':' 
			: '';

		return $prefix . $group . ':' . $key;
	}

	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( wp_suspend_cache_addition() || isset( $this->non_persistent_groups[ $group ] ) ) {
			return false;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				$exists = $this->redis->exists( $cache_key );
				if ( $exists ) {
					return false;
				}
				return $this->set( $key, $data, $group, $expire );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function delete( $key, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				$this->redis->del( $cache_key );
			} catch ( Exception $e ) {
				// Fail silently
			}
		}

		unset( $this->cache[ $group ][ $key ] );
		return true;
	}

	public function flush() {
		if ( $this->redis ) {
			try {
				$this->redis->flushDB();
			} catch ( Exception $e ) {
				// Fail silently
			}
		}

		$this->cache = array();
		return true;
	}

	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$cache_key = $this->get_key( $key, $group );

		if ( ! $force && isset( $this->cache[ $group ][ $key ] ) ) {
			$found = true;
			$this->cache_hits++;
			return $this->cache[ $group ][ $key ];
		}

		if ( $this->redis && ! isset( $this->non_persistent_groups[ $group ] ) ) {
			try {
				$value = $this->redis->get( $cache_key );
				
				if ( $value === false ) {
					$found = false;
					$this->cache_misses++;
					return false;
				}

				$found = true;
				$this->cache_hits++;
				$data = maybe_unserialize( $value );
				$this->cache[ $group ][ $key ] = $data;
				return $data;
			} catch ( Exception $e ) {
				$found = false;
				$this->cache_misses++;
				return false;
			}
		}

		$found = false;
		$this->cache_misses++;
		return false;
	}

	public function incr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				return $this->redis->incrBy( $cache_key, $offset );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] += $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function decr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				return $this->redis->decrBy( $cache_key, $offset );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] -= $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				$exists = $this->redis->exists( $cache_key );
				if ( ! $exists ) {
					return false;
				}
				return $this->set( $key, $data, $group, $expire );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			$this->cache[ $group ][ $key ] = $data;
			return true;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( $this->redis ) {
			try {
				$value = maybe_serialize( $data );
				
				if ( $expire > 0 ) {
					$result = $this->redis->setex( $cache_key, $expire, $value );
				} else {
					$result = $this->redis->set( $cache_key, $value );
				}
				
				if ( $result ) {
					$this->cache[ $group ][ $key ] = $data;
				}
				
				return $result;
			} catch ( Exception $e ) {
				return false;
			}
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $blog_id;
		return true;
	}

	public function add_global_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->global_groups = array_merge( $this->global_groups, $groups );
	}

	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->non_persistent_groups = array_merge( $this->non_persistent_groups, $groups );
	}
}
PHP;

		// Replace placeholders
		$content = str_replace( 'REDIS_HOST', "'" . addslashes( $host ) . "'", $content );
		$content = str_replace( 'REDIS_PORT', intval( $port ), $content );
		$content = str_replace( 'PASSWORD_CODE', $password_code, $content );
		$content = str_replace( 'DATABASE_CODE', $database_code, $content );

		return $content;
	}

	/**
	 * Generate Memcached drop-in content
	 *
	 * @param array $options Plugin options.
	 * @return string Drop-in file content.
	 */
	private function generate_memcached_dropin( $options ) {
		$servers = array();
		
		if ( ! empty( $options['memcached_servers'] ) ) {
			// Parse servers from textarea (one per line)
			$server_list = array_filter( array_map( 'trim', explode( "\n", $options['memcached_servers'] ) ) );
			foreach ( $server_list as $server ) {
				$parts = explode( ':', $server );
				$servers[] = array(
					'host' => trim( $parts[0] ),
					'port' => isset( $parts[1] ) ? intval( trim( $parts[1] ) ) : 11211,
				);
			}
		} else {
			// Default server
			$host = ! empty( $options['memcached_host'] ) ? $options['memcached_host'] : '127.0.0.1';
			$port = ! empty( $options['memcached_port'] ) ? intval( $options['memcached_port'] ) : 11211;
			$servers[] = array(
				'host' => $host,
				'port' => $port,
			);
		}

		return $this->get_memcached_dropin_template( $servers );
	}

	/**
	 * Get Memcached drop-in template
	 *
	 * @param array $servers Array of server configurations.
	 * @return string Template content.
	 */
	private function get_memcached_dropin_template( $servers ) {
		$servers_code = '';
		foreach ( $servers as $server ) {
			$servers_code .= "\t\t\$this->memcached->addServer( '" . addslashes( $server['host'] ) . "', " . intval( $server['port'] ) . " );\n";
		}

		$content = <<<'PHP'
<?php
/**
 * Object Cache Drop-in - Memcached Backend
 * Generated by VelocityWP
 * 
 * @package VelocityWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// WordPress cache functions
function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, $expire );
}

function wp_cache_close() {
	return true;
}

function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $offset, $group );
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $offset, $group );
}

function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache();
}

function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $data, $group, $expire );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $data, $group, $expire );
}

function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	return $wp_object_cache->switch_to_blog( $blog_id );
}

function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_global_groups( $groups );
}

function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_non_persistent_groups( $groups );
}

class WP_Object_Cache {
	private $memcached;
	private $cache = array();
	private $global_groups = array();
	private $non_persistent_groups = array();
	public $cache_hits = 0;
	public $cache_misses = 0;
	private $blog_prefix;
	private $multisite;

	public function __construct() {
		global $blog_id, $table_prefix;
		
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? $blog_id : $table_prefix;
		
		try {
			$this->memcached = new Memcached();
SERVERS_CODE
		} catch ( Exception $e ) {
			$this->memcached = null;
		}
	}

	private function get_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = $this->multisite && ! isset( $this->global_groups[ $group ] ) 
			? $this->blog_prefix . ':' 
			: '';

		return $prefix . $group . ':' . $key;
	}

	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( wp_suspend_cache_addition() || isset( $this->non_persistent_groups[ $group ] ) ) {
			return false;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( $this->memcached ) {
			try {
				$value = $this->memcached->get( $cache_key );
				if ( $value !== false ) {
					return false;
				}
				return $this->set( $key, $data, $group, $expire );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function delete( $key, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->memcached ) {
			try {
				$this->memcached->delete( $cache_key );
			} catch ( Exception $e ) {
				// Fail silently
			}
		}

		unset( $this->cache[ $group ][ $key ] );
		return true;
	}

	public function flush() {
		if ( $this->memcached ) {
			try {
				$this->memcached->flush();
			} catch ( Exception $e ) {
				// Fail silently
			}
		}

		$this->cache = array();
		return true;
	}

	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$cache_key = $this->get_key( $key, $group );

		if ( ! $force && isset( $this->cache[ $group ][ $key ] ) ) {
			$found = true;
			$this->cache_hits++;
			return $this->cache[ $group ][ $key ];
		}

		if ( $this->memcached && ! isset( $this->non_persistent_groups[ $group ] ) ) {
			try {
				$value = $this->memcached->get( $cache_key );
				
				if ( $value === false ) {
					$found = false;
					$this->cache_misses++;
					return false;
				}

				$found = true;
				$this->cache_hits++;
				$data = is_object( $value ) ? clone $value : $value;
				$this->cache[ $group ][ $key ] = $data;
				return $data;
			} catch ( Exception $e ) {
				$found = false;
				$this->cache_misses++;
				return false;
			}
		}

		$found = false;
		$this->cache_misses++;
		return false;
	}

	public function incr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->memcached ) {
			try {
				return $this->memcached->increment( $cache_key, $offset );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] += $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function decr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( $this->memcached ) {
			try {
				return $this->memcached->decrement( $cache_key, $offset );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] -= $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		if ( $this->memcached ) {
			$cache_key = $this->get_key( $key, $group );
			try {
				$value = $this->memcached->get( $cache_key );
				if ( $value === false ) {
					return false;
				}
				return $this->set( $key, $data, $group, $expire );
			} catch ( Exception $e ) {
				return false;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			$this->cache[ $group ][ $key ] = $data;
			return true;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( $this->memcached ) {
			try {
				$result = $this->memcached->set( $cache_key, $data, $expire > 0 ? $expire : 0 );
				
				if ( $result ) {
					$this->cache[ $group ][ $key ] = $data;
				}
				
				return $result;
			} catch ( Exception $e ) {
				return false;
			}
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $blog_id;
		return true;
	}

	public function add_global_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->global_groups = array_merge( $this->global_groups, $groups );
	}

	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->non_persistent_groups = array_merge( $this->non_persistent_groups, $groups );
	}
}
PHP;

		// Replace placeholders
		$content = str_replace( 'SERVERS_CODE', $servers_code, $content );

		return $content;
	}

	/**
	 * Generate APCu drop-in content
	 *
	 * @param array $options Plugin options.
	 * @return string Drop-in file content.
	 */
	private function generate_apcu_dropin( $options ) {
		return $this->get_apcu_dropin_template();
	}

	/**
	 * Get APCu drop-in template
	 *
	 * @return string Template content.
	 */
	private function get_apcu_dropin_template() {
		return <<<'PHP'
<?php
/**
 * Object Cache Drop-in - APCu Backend
 * Generated by VelocityWP
 * 
 * @package VelocityWP
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function wp_cache_add( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->add( $key, $data, $group, $expire );
}

function wp_cache_close() {
	return true;
}

function wp_cache_decr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->decr( $key, $offset, $group );
}

function wp_cache_delete( $key, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->delete( $key, $group );
}

function wp_cache_flush() {
	global $wp_object_cache;
	return $wp_object_cache->flush();
}

function wp_cache_get( $key, $group = '', $force = false, &$found = null ) {
	global $wp_object_cache;
	return $wp_object_cache->get( $key, $group, $force, $found );
}

function wp_cache_incr( $key, $offset = 1, $group = '' ) {
	global $wp_object_cache;
	return $wp_object_cache->incr( $key, $offset, $group );
}

function wp_cache_init() {
	global $wp_object_cache;
	$wp_object_cache = new WP_Object_Cache();
}

function wp_cache_replace( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->replace( $key, $data, $group, $expire );
}

function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
	global $wp_object_cache;
	return $wp_object_cache->set( $key, $data, $group, $expire );
}

function wp_cache_switch_to_blog( $blog_id ) {
	global $wp_object_cache;
	return $wp_object_cache->switch_to_blog( $blog_id );
}

function wp_cache_add_global_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_global_groups( $groups );
}

function wp_cache_add_non_persistent_groups( $groups ) {
	global $wp_object_cache;
	return $wp_object_cache->add_non_persistent_groups( $groups );
}

class WP_Object_Cache {
	private $cache = array();
	private $global_groups = array();
	private $non_persistent_groups = array();
	public $cache_hits = 0;
	public $cache_misses = 0;
	private $blog_prefix;
	private $multisite;

	public function __construct() {
		global $blog_id, $table_prefix;
		
		$this->multisite = is_multisite();
		$this->blog_prefix = $this->multisite ? $blog_id : $table_prefix;
	}

	private function get_key( $key, $group = 'default' ) {
		if ( empty( $group ) ) {
			$group = 'default';
		}

		$prefix = $this->multisite && ! isset( $this->global_groups[ $group ] ) 
			? $this->blog_prefix . ':' 
			: '';

		return $prefix . $group . ':' . $key;
	}

	public function add( $key, $data, $group = 'default', $expire = 0 ) {
		if ( wp_suspend_cache_addition() || isset( $this->non_persistent_groups[ $group ] ) ) {
			return false;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_fetch' ) ) {
			$exists = apcu_exists( $cache_key );
			if ( $exists ) {
				return false;
			}
			return $this->set( $key, $data, $group, $expire );
		}

		if ( isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function delete( $key, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_delete' ) ) {
			apcu_delete( $cache_key );
		}

		unset( $this->cache[ $group ][ $key ] );
		return true;
	}

	public function flush() {
		if ( function_exists( 'apcu_clear_cache' ) ) {
			apcu_clear_cache();
		}

		$this->cache = array();
		return true;
	}

	public function get( $key, $group = 'default', $force = false, &$found = null ) {
		$cache_key = $this->get_key( $key, $group );

		if ( ! $force && isset( $this->cache[ $group ][ $key ] ) ) {
			$found = true;
			$this->cache_hits++;
			return $this->cache[ $group ][ $key ];
		}

		if ( function_exists( 'apcu_fetch' ) && ! isset( $this->non_persistent_groups[ $group ] ) ) {
			$success = false;
			$value = apcu_fetch( $cache_key, $success );
			
			if ( $success ) {
				$found = true;
				$this->cache_hits++;
				$this->cache[ $group ][ $key ] = $value;
				return $value;
			}
		}

		$found = false;
		$this->cache_misses++;
		return false;
	}

	public function incr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_inc' ) ) {
			$result = apcu_inc( $cache_key, $offset, $success );
			if ( $success ) {
				return $result;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] += $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function decr( $key, $offset = 1, $group = 'default' ) {
		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_dec' ) ) {
			$result = apcu_dec( $cache_key, $offset, $success );
			if ( $success ) {
				return $result;
			}
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		if ( ! is_numeric( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] -= $offset;
		return $this->cache[ $group ][ $key ];
	}

	public function replace( $key, $data, $group = 'default', $expire = 0 ) {
		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_exists' ) ) {
			$exists = apcu_exists( $cache_key );
			if ( ! $exists ) {
				return false;
			}
			return $this->set( $key, $data, $group, $expire );
		}

		if ( ! isset( $this->cache[ $group ][ $key ] ) ) {
			return false;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function set( $key, $data, $group = 'default', $expire = 0 ) {
		if ( isset( $this->non_persistent_groups[ $group ] ) ) {
			$this->cache[ $group ][ $key ] = $data;
			return true;
		}

		$cache_key = $this->get_key( $key, $group );

		if ( function_exists( 'apcu_store' ) ) {
			$result = apcu_store( $cache_key, $data, $expire > 0 ? $expire : 0 );
			
			if ( $result ) {
				$this->cache[ $group ][ $key ] = $data;
			}
			
			return $result;
		}

		$this->cache[ $group ][ $key ] = $data;
		return true;
	}

	public function switch_to_blog( $blog_id ) {
		$this->blog_prefix = $blog_id;
		return true;
	}

	public function add_global_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->global_groups = array_merge( $this->global_groups, $groups );
	}

	public function add_non_persistent_groups( $groups ) {
		$groups = (array) $groups;
		$groups = array_fill_keys( $groups, true );
		$this->non_persistent_groups = array_merge( $this->non_persistent_groups, $groups );
	}
}
PHP;
	}

	/**
	 * Install object cache drop-in (alias for generate_dropin)
	 *
	 * @return bool|WP_Error Success or error.
	 */
	public function install_dropin() {
		return $this->generate_dropin();
	}

	/**
	 * Remove object cache drop-in
	 *
	 * @return bool Success.
	 */
	public function remove_dropin() {
		$dropin_file = WP_CONTENT_DIR . '/object-cache.php';

		if ( ! file_exists( $dropin_file ) ) {
			return true;
		}

		// Check if it's our drop-in
		$content = file_get_contents( $dropin_file );
		if ( strpos( $content, 'VelocityWP' ) === false ) {
			return false;
		}

		$result = unlink( $dropin_file );
		
		if ( $result ) {
			$this->is_active = false;
		}

		return $result;
	}

	/**
	 * Flush object cache
	 *
	 * @return bool Success.
	 */
	public function flush_cache() {
		return wp_cache_flush();
	}

	/**
	 * Get cache statistics
	 *
	 * @return array Cache stats.
	 */
	public function get_stats() {
		global $wp_object_cache;

		$stats = array(
			'backend'  => $this->backend ? $this->backend : 'none',
			'active'   => $this->is_active,
			'hits'     => 0,
			'misses'   => 0,
			'ratio'    => 0,
			'memory'   => '',
			'entries'  => 0,
		);

		if ( isset( $wp_object_cache->cache_hits ) ) {
			$stats['hits'] = $wp_object_cache->cache_hits;
		}

		if ( isset( $wp_object_cache->cache_misses ) ) {
			$stats['misses'] = $wp_object_cache->cache_misses;
		}

		$total = $stats['hits'] + $stats['misses'];
		if ( $total > 0 ) {
			$stats['ratio'] = round( ( $stats['hits'] / $total ) * 100, 2 );
		}

		// Get memory usage based on backend
		if ( $this->backend === 'redis' && class_exists( 'Redis' ) ) {
			try {
				$redis = new Redis();
				$options = get_option( 'velocitywp_options', array() );
				$host = ! empty( $options['redis_host'] ) ? $options['redis_host'] : '127.0.0.1';
				$port = ! empty( $options['redis_port'] ) ? intval( $options['redis_port'] ) : 6379;
				
				if ( $redis->connect( $host, $port, 1 ) ) {
					if ( ! empty( $options['redis_password'] ) ) {
						$redis->auth( $options['redis_password'] );
					}
					$info = $redis->info( 'memory' );
					if ( isset( $info['used_memory_human'] ) ) {
						$stats['memory'] = $info['used_memory_human'];
					}
					$stats['entries'] = $redis->dbSize();
					$redis->close();
				}
			} catch ( Exception $e ) {
				// Fail silently
			}
		} elseif ( $this->backend === 'memcached' && class_exists( 'Memcached' ) ) {
			try {
				$memcached = new Memcached();
				$options = get_option( 'velocitywp_options', array() );
				$host = ! empty( $options['memcached_host'] ) ? $options['memcached_host'] : '127.0.0.1';
				$port = ! empty( $options['memcached_port'] ) ? intval( $options['memcached_port'] ) : 11211;
				$memcached->addServer( $host, $port );
				$memstats = $memcached->getStats();
				if ( ! empty( $memstats ) ) {
					$server_key = $host . ':' . $port;
					if ( isset( $memstats[ $server_key ]['bytes'] ) ) {
						$stats['memory'] = size_format( $memstats[ $server_key ]['bytes'], 2 );
					}
					if ( isset( $memstats[ $server_key ]['curr_items'] ) ) {
						$stats['entries'] = $memstats[ $server_key ]['curr_items'];
					}
				}
			} catch ( Exception $e ) {
				// Fail silently
			}
		} elseif ( $this->backend === 'apcu' && function_exists( 'apcu_cache_info' ) ) {
			try {
				$info = apcu_cache_info( true );
				if ( isset( $info['mem_size'] ) ) {
					$stats['memory'] = size_format( $info['mem_size'], 2 );
				}
				if ( isset( $info['num_entries'] ) ) {
					$stats['entries'] = $info['num_entries'];
				}
			} catch ( Exception $e ) {
				// Fail silently
			}
		}

		return $stats;
	}

	/**
	 * Get cache statistics (alias for get_stats)
	 *
	 * @return array Cache stats.
	 */
	public function get_statistics() {
		return $this->get_stats();
	}

	/**
	 * Admin notices
	 */
	public function admin_notices() {
		$options = get_option( 'velocitywp_options', array() );

		if ( empty( $options['object_cache'] ) ) {
			return;
		}

		if ( ! $this->is_active ) {
			?>
			<div class="notice notice-warning">
				<p>
					<?php esc_html_e( 'VelocityWP: Object cache is enabled but the drop-in file is not installed.', 'velocitywp' ); ?>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=velocitywp-settings&tab=advanced' ) ); ?>">
						<?php esc_html_e( 'Install now', 'velocitywp' ); ?>
					</a>
				</p>
			</div>
			<?php
		}

		if ( empty( $this->backend ) ) {
			?>
			<div class="notice notice-error">
				<p>
					<?php esc_html_e( 'VelocityWP: No object cache backend available. Please install Redis, Memcached, or APCu.', 'velocitywp' ); ?>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * AJAX handler to test cache connection
	 */
	public function ajax_test_cache() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$backend = isset( $_POST['backend'] ) ? sanitize_text_field( $_POST['backend'] ) : '';

		$available = false;
		switch ( $backend ) {
			case 'redis':
				$available = $this->test_redis();
				break;
			case 'memcached':
				$available = $this->test_memcached();
				break;
			case 'apcu':
				$available = $this->test_apcu();
				break;
		}

		if ( $available ) {
			wp_send_json_success( array( 'message' => sprintf( __( '%s is available and working', 'velocitywp' ), ucfirst( $backend ) ) ) );
		} else {
			wp_send_json_error( array( 'message' => sprintf( __( '%s is not available or not working', 'velocitywp' ), ucfirst( $backend ) ) ) );
		}
	}

	/**
	 * AJAX handler to flush cache
	 */
	public function ajax_flush_cache() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$result = $this->flush_cache();

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Object cache flushed', 'velocitywp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to flush cache', 'velocitywp' ) ) );
		}
	}

	/**
	 * AJAX handler to install dropin
	 */
	public function ajax_install_dropin() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$result = $this->generate_dropin();

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		} else {
			wp_send_json_success( array( 'message' => __( 'Object cache drop-in installed successfully', 'velocitywp' ) ) );
		}
	}

	/**
	 * AJAX handler to remove dropin
	 */
	public function ajax_remove_dropin() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$result = $this->remove_dropin();

		if ( $result ) {
			wp_send_json_success( array( 'message' => __( 'Object cache drop-in removed successfully', 'velocitywp' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Failed to remove drop-in', 'velocitywp' ) ) );
		}
	}

	/**
	 * AJAX handler to get cache stats
	 */
	public function ajax_get_stats() {
		check_ajax_referer( 'velocitywp-admin-nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'velocitywp' ) ) );
		}

		$stats = $this->get_stats();
		wp_send_json_success( $stats );
	}
}
