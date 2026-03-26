<article class="kdnaform-splash" data-js="kdnaform-splash-page">

	<header class="kdnaform-splash__header">
		<img class="kdnaform-logo" src="<?php echo esc_url( KDNACommon::get_base_url() ); ?>/images/logos/kdna-logo-white.svg" alt="KDNA Forms"/>
		<h1><?php esc_html_e( 'New Fields Added in KDNA Forms 2.9!', 'kdnaforms' ); ?></h1>
		<p><?php esc_html_e( 'The new Image Choice and Multiple Choice fields give you more flexibility and control when creating forms.', 'kdnaforms' ); ?></p>
		<a class="kdnaform-button kdnaform-button--size-height-xxl kdnaform-button--white kdnaform-button--width-auto kdnaform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'kdnaforms' ); ?>">
			<span class="kdnaform-button__text kdnaform-button__text--inactive kdnaform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'kdnaforms' ); ?></span>
			<span class="kdnaform-common-icon kdnaform-common-icon--arrow-narrow-right kdnaform-button__icon" aria-hidden="true"></span>
		</a>
		<div class="kdnaform-reviews">
            <ul class="kdnaform-reviews__list">
                <li class="kdnaform-reviews__list-item kdnaform-reviews__list-item--g2">
                    <a
                        href="https://www.g2.com/products/kdna-forms/reviews"
                        title="<?php esc_html_e( 'Read reviews of KDNA Forms on G2', 'kdnaforms' ); ?>"
                        target="_blank"
                        class="kdnaform-reviews__link"
                    >
                        <span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
                        <img
                            src="<?php echo esc_attr( $this->img_dir ) . 'g2.svg';  ?>"
                            alt="<?php esc_attr_e( 'G2 logo', 'kdnaforms' ); ?>"
                            class="kdnaform-reviews__logo"
                        />
                        <span class="kdnaform-reviews__stars kdnaform-reviews__stars--icon" aria-hidden="true">
                            <span class="kdnaform-common-icon kdnaform-common-icon--star"></span>
                            <span class="kdnaform-common-icon kdnaform-common-icon--star"></span>
                            <span class="kdnaform-common-icon kdnaform-common-icon--star"></span>
                            <span class="kdnaform-common-icon kdnaform-common-icon--star"></span>
                            <span class="kdnaform-common-icon kdnaform-common-icon--star"></span>
				        </span>
                        200+ <?php esc_html_e( '4.7 Stars', 'kdnaforms' ); ?>
                    </a>
                </li>
                <li class="kdnaform-reviews__list-item kdnaform-reviews__list-item--trustpilot">
                    <a
                        href="https://www.trustpilot.com/review/kdnaforms.com"
                        title="<?php esc_html_e( 'Read reviews of KDNA Forms on Trustpilot', 'kdnaforms' ); ?>"
                        class="kdnaform-reviews__link"
                        target="_blank"
                    >
                        <span class="screen-reader-text"><?php echo esc_html__( '(opens in a new tab)', 'kdnaforms' ); ?></span>
                        <img
                            src="<?php echo esc_attr( $this->img_dir ) . 'trustpilot.svg'; ?>"
                            alt="<?php esc_attr_e( 'Trustpilot logo', 'kdnaforms' ); ?>"
                            class="kdnaform-reviews__logo"
                        />
                        <span class="kdnaform-reviews__stars kdnaform-reviews__stars--image">
                            <img
                                src="<?php echo esc_attr( $this->img_dir ) . 'trustpilot-rating.svg'; ?>"
                                alt="<?php esc_attr_e( 'Trustpilot rating', 'kdnaforms' ); ?>"
                                class="kdnaform-reviews__stars-image"
                            />
				        </span>
                        50+ <?php esc_html_e( '4.4 Stars', 'kdnaforms' ); ?>
                    </a>
                </li>
            </ul>
		</div>
	</header>

	<div class="kdnaform-splash__body">

        <div class="kdnaform-splash__sections">
            <?php
            $text  = '<h3>' . esc_html__( 'Image Choice Field', 'kdnaforms' ) . '</h3>
                <p>' . esc_html__( 'A picture is worth a thousand words! The new Image Choice field lets you add stylish images straight from the media library to your choices. Easily create beautiful forms with eye-catching images that speak to your users.', 'kdnaforms' ) . '</p>
                <a href="https://docs.kdnaforms.com/image-choice-field/" target="_blank" class="kdnaform-button kdnaform-button--size-height-xl kdnaform-button--primary-new kdnaform-button--width-auto" title="' . esc_attr__( 'Read more about the Image Choice field', 'kdnaforms' ) . '">
                <span class="kdnaform-button__text kdnaform-button__text--inactive kdnaform-typography--size-text-sm">' . esc_html__( 'Read More', 'kdnaforms' ) . '</span>
                <span class="screen-reader-text">' . esc_html__( 'About the Image Choice field', 'kdnaforms' ) . '</span>
				<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>
				&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a>';
            $image = array(
                'src' => $this->img_dir . 'image-choice-field.png',
                'alt' => esc_attr__( 'Screenshot of the Image Choice field in KDNA Forms 2.9', 'kdnaforms' ),
            );

            echo wp_kses_post(
                $this->tags->equal_columns(
                    array(
                        'columns' => array(
                            $this->tags->build_image_html( $image ),
                            $text,
                        ),
                        'container_classes' => 'column--vertical-center',
                    ),
                )
            );

            $text  = '<h3>' . esc_html__( 'Multiple Choice Field', 'kdnaforms' ) . '</h3>
                <p>' . esc_html__( 'The Multiple Choice field is a new, flexible way to let users choose one or many options. Gather the information you need, while ensuring a high-end experience for those submitting the form.', 'kdnaforms' ) . '</p>
                <a href="https://docs.kdnaforms.com/multiple-choice-field/" target="_blank" class="kdnaform-button kdnaform-button--size-height-xl kdnaform-button--primary-new kdnaform-button--width-auto" title="' . esc_attr__( 'Read more about the Multiple Choice field', 'kdnaforms' ) . '">
                <span class="kdnaform-button__text kdnaform-button__text--inactive kdnaform-typography--size-text-sm">' . esc_html__( 'Read More', 'kdnaforms' ) . '</span>
                <span class="screen-reader-text">' . esc_html__( 'About the Multiple Choice field', 'kdnaforms' ) . '</span>
				<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>
                &nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a>';
            $image = array(
                'src' => $this->img_dir . 'multiple-choice-field.png',
                'alt' => esc_attr__( 'Screenshot of the Multiple Choice field in KDNA Forms 2.9', 'kdnaforms' ),
            );

            echo wp_kses_post(
                $this->tags->equal_columns(
                    array(
                        'columns' => array(
                            $text,
                            $this->tags->build_image_html( $image ),
                        ),
                        'container_classes' => 'column--vertical-center',
                    ),
                )
            );

            $col1_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'editor-design-improvements-icon.svg',
                    'alt' => esc_attr__( 'Icon of color swatches', 'kdnaforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col1 = $col1_icon . '<h4>' . esc_html__( 'Editor Design Improvements', 'kdnaforms' ) . '</h4>
                <p>' . esc_html__( 'We’ve brought our beautiful Orbital form theme into the form editor! With 2.9 you’ll find a more consistent and visually-pleasing form editing experience, closely mirroring how your form will look on the front end.', 'kdnaforms' ) . ' <a href="https://docs.kdnaforms.com/kdna-forms-2-9-key-features/" title="' . esc_attr__( 'Read more about the KDNA Forms 2.9 editor design improvements', 'kdnaforms' ) . '" target="_blank">' . esc_html__( 'Read More', 'kdnaforms' ) . '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a></p>';

            $col2_icon = $style_icon = $this->tags->build_image_html(
                array(
                    'src' => $this->img_dir . 'editor-accessibility-improvements-icon.svg',
                    'alt' => esc_attr__( 'Icon of accessibility symbol', 'kdnaforms' ),
                    'width' => '52px',
                    'height' => '52px',
                    'class' => 'image--width-auto',
                )
            );
            $col2 = $col2_icon . '<h4>' . esc_html__( 'Editor Accessibility Improvements', 'kdnaforms' ) . '</h4>
                <p>' . esc_html__( 'As part of our continuing commitment to make form building available to everyone, we have improved the accessibility of the form editor. If you rely on keyboard navigation or screen readers, you’ll now have an easier time navigating the field settings.', 'kdnaforms' ) . ' <a href="https://docs.kdnaforms.com/kdna-forms-2-9-key-features/" title="' . esc_attr__( 'Read more about the KDNA Forms 2.9 editor accessibility improvements', 'kdnaforms' ) . '" target="_blank">' . esc_html__( 'Read More', 'kdnaforms' ) . '<span class="screen-reader-text">' . esc_html__( '(opens in a new tab)', 'kdnaforms' ) . '</span>&nbsp;<span class="kdnaform-icon kdnaform-icon--external-link" aria-hidden="true"></span></a></p>';

            echo wp_kses_post(
                $this->tags->equal_columns(
                    array(
                        'columns' => array(
                            $col1,
                            $col2,
                        ),
                        'container_classes' => 'column--vertical-center',
                    ),
                )
            );
            ?>
        </div>

		<footer class="kdnaform-splash__footer">
			<h4>
				<?php esc_html_e( 'Ready to get started?', 'kdnaforms' ); ?>
			</h4>
			<p>
				<?php esc_html_e( 'We believe there\'s a better way to manage your data and forms. Are you ready to create a form? Let\'s go!', 'kdnaforms' ); ?>
			</p>
			<a class="kdnaform-button kdnaform-button--size-height-xxl kdnaform-button--white kdnaform-button--width-auto kdnaform-button--icon-trailing"  href="<?php echo esc_url( admin_url( 'admin.php?page=gf_new_form' ) ); ?>" title="<?php esc_attr_e( 'Get started with a new form', 'kdnaforms' ); ?>">
				<span class="kdnaform-button__text kdnaform-button__text--inactive kdnaform-typography--size-text-md"><?php esc_html_e( 'Get Started', 'kdnaforms' ); ?></span>
				<span class="kdnaform-common-icon kdnaform-common-icon--arrow-narrow-right kdnaform-button__icon" aria-hidden="true"></span>
			</a>
		</footer>

		<div class="kdnaform-splash__background kdnaform-splash__background-one"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-two"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-three"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-four"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-five"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-six"></div>
		<div class="kdnaform-splash__background kdnaform-splash__background-seven"></div>

	</div>

</article>
