const gulp = require( 'gulp' );

gulp.task( 'build:php:base', function () {
	return gulp
		.src( [ 'src/*.php' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task( 'build:php:other', function () {
	// TODO: Split these
	return gulp
		.src( [ 'src/**/*.css', 'src/**/*.js', 'src/**/*.php', 'src/**/*.png', 'src/**/*.txt' ] )
		.pipe( gulp.dest( 'dist/' ) );
} );

gulp.task(
	'build:php',
	gulp.parallel(
		'build:php:base',
		'build:php:other'
	)
);

gulp.task(
	'build',
	gulp.parallel(
		'build:php'
	)
);
