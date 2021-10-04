<?php

namespace BoxUk\Dictator\WpCli;

use BoxUk\Dictator\State;
use BoxUk\Dictator\State\Network;
use BoxUk\Dictator\State\Site;
use Symfony\Component\Serializer\SerializerInterface;
use WP_CLI;
use WP_CLI_Command;

class Dictator extends WP_CLI_Command
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        parent::__construct();
        $this->serializer = $serializer;
    }

    /**
     * Export the State of WordPress to a state file.
     *
     * ## OPTIONS
     *
     * <state>
     * : State to export
     *
     * <file>
     * : Where the state should be exported to
     *
     * [--regions=<regions>]
     * : Limit the export to one or more regions.
     *
     * [--force]
     * : Forcefully overwrite an existing state file if one exists.
     *
     * @subcommand export
     *
     * @param array $args Args.
     * @param array $assoc_args Assoc Args.
     *
     * @throws \WP_CLI\ExitException Exits on error, such as bad state supplied.
     */
    public function export( array $args, array $assoc_args )
    {

        list( $state, $file ) = $args;

        if ( file_exists( $file ) && ! isset( $assoc_args['force'] ) ) {
            WP_CLI::confirm( 'Are you sure you want to overwrite the existing state file?' );
        }

        // TODO: Add some way of regions to get their data from the db

//        $state_obj = Dictator::get_state_obj( $state );
//        if ( ! $state_obj ) {
//            WP_CLI::error( 'Invalid state supplied.' );
//        }
//
//        $limited_regions = ! empty( $assoc_args['regions'] ) ? explode( ',', $assoc_args['regions'] ) : array();
//
//        // Build the state's data.
//        $state_data = array( 'state' => $state );
//        foreach ( $state_obj->get_regions() as $region_obj ) {
//
//            $region_name = $state_obj->get_region_name( $region_obj );
//
//            if ( $limited_regions && ! in_array( $region_name, $limited_regions, true ) ) {
//                continue;
//            }
//
//            $state_data[ $region_name ] = $region_obj->get_current_data();
//        }
//
//        $this->write_state_file( $state_data, $file );

        WP_CLI::success( 'State written to file.' );
    }

    /**
     * Impose a given state file onto WordPress.
     *
     * ## OPTIONS
     *
     * <file>
     * : State file to impose
     *
     * [--regions=<regions>]
     * : Limit the imposition to one or more regions.
     *
     * @subcommand impose
     *
     * @param array $args Args.
     * @param array $assoc_args Assoc args.
     */
    public function impose( array $args, array $assoc_args ): void
    {

        list( $file ) = $args;

        $state = $this->loadStateFile( $file );

//        $state_obj = Dictator::get_state_obj( $yaml['state'], $yaml );
//
//        $limited_regions = ! empty( $assoc_args['regions'] ) ? explode( ',', $assoc_args['regions'] ) : array();
//
//        foreach ( $state_obj->get_regions() as $region_obj ) {
//
//            $region_name = $state_obj->get_region_name( $region_obj );
//
//            if ( $limited_regions && ! in_array( $region_name, $limited_regions, true ) ) {
//                continue;
//            }
//
//            if ( $region_obj->is_under_accord() ) {
//                continue;
//            }
//
//            WP_CLI::line( sprintf( '%s:', $region_name ) );
//
//            // Render the differences for the region.
//            $differences = $region_obj->get_differences();
//            foreach ( $differences as $slug => $difference ) {
//                $this->show_difference( $slug, $difference );
//
//                $to_impose = \Dictator::array_diff_recursive( $difference['dictated'], $difference['current'] );
//                $ret       = $region_obj->impose( $slug, $difference['dictated'] );
//                if ( is_wp_error( $ret ) ) {
//                    WP_CLI::warning( $ret->get_error_message() );
//                }
//            }
//        }

        WP_CLI::success( 'The Dictator has imposed upon the State of WordPress.' );

    }

    /**
     * Load a given Yaml state file.
     *
     * @param string $filename Filename to load state from.
     * @return object
     */
    private function loadStateFile( string $filename ): State {

        if ( ! file_exists( $filename ) ) {
            WP_CLI::error( sprintf( "File doesn't exist: %s", $filename ) );
        }

        $yaml = file_get_contents( $filename ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        if ( empty( $yaml ) ) {
            WP_CLI::error( sprintf( "Doesn't appear to be a Yaml file: %s", $filename ) );
        }

        $type = Site::class;
        if (strpos($yaml, 'network:') === 0) {
            $type = Network::class;
        }

        /** @var State $state */
        $state = $this->serializer->deserialize($yaml, $type, 'yaml');

        return $state;
    }
}
