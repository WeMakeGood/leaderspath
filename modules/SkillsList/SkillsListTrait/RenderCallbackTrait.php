<?php
/**
 * SkillsList::render_callback()
 *
 * @package LeadersPath\Modules\SkillsList
 * @since 0.1.0
 */

declare(strict_types=1);

namespace LeadersPath\Modules\SkillsList\SkillsListTrait;

if ( ! defined( 'ABSPATH' ) ) {
	die( 'Direct access forbidden.' );
}

// phpcs:disable ET.Sniffs.ValidVariableName.UsedPropertyNotSnakeCase -- WP uses snakeCase in \WP_Block_Parser_Block

use ET\Builder\Packages\Module\Module;
use ET\Builder\Framework\Utility\HTMLUtility;
use ET\Builder\FrontEnd\BlockParser\BlockParserStore;
use ET\Builder\Packages\Module\Options\Element\ElementComponents;
use LeadersPath\Modules\SkillsList\SkillsList;

/**
 * Render callback trait for SkillsList.
 *
 * @since 0.1.0
 */
trait RenderCallbackTrait {

	/**
	 * Get the current lesson post ID.
	 *
	 * Uses get_queried_object_id() for Theme Builder templates,
	 * with get_the_ID() as fallback.
	 *
	 * @since 0.1.0
	 *
	 * @return int Post ID, or 0 if not found.
	 */
	public static function get_lesson_id(): int {
		$post_id = get_queried_object_id();

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		return (int) $post_id;
	}

	/**
	 * Get skills for the current lesson.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array{id: int, title: string, name: string, description: string, compatibility: string, version: string, package_url: string}>
	 */
	public static function get_skills(): array {
		$post_id = self::get_lesson_id();
		$skills  = [];

		if ( ! $post_id ) {
			return $skills;
		}

		$skill_ids = get_field( 'chatbot_skills', $post_id ) ?: [];

		foreach ( $skill_ids as $skill_id ) {
			$post = get_post( $skill_id );

			if ( ! $post ) {
				continue;
			}

			// Get the skill package attachment URL.
			$package_id  = get_field( 'skill_package', $post->ID );
			$package_url = $package_id ? wp_get_attachment_url( $package_id ) : '';

			$skills[] = [
				'id'            => $post->ID,
				'title'         => $post->post_title,
				'name'          => get_field( 'skill_name', $post->ID ) ?: $post->post_title,
				'description'   => get_field( 'skill_description', $post->ID ) ?: '',
				'compatibility' => get_field( 'skill_compatibility', $post->ID ) ?: '',
				'version'       => get_field( 'skill_version', $post->ID ) ?: '',
				'package_url'   => $package_url,
			];
		}

		return $skills;
	}

	/**
	 * Skills List module render callback which outputs server side rendered HTML on the Front-End.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Block attributes that were saved by VB.
	 * @param string         $content  Block content.
	 * @param \WP_Block      $block    Parsed block object that being rendered.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML rendered of Skills List module.
	 */
	public static function render_callback( $attrs, $content, $block, $elements ): string {
		$skills = self::get_skills();

		// Render module title.
		$title = $elements->render(
			[
				'attrName' => 'title',
			]
		);

		// Build content based on whether we have skills.
		if ( empty( $skills ) ) {
			$items_html = self::render_empty_state( $attrs, $elements );
		} else {
			$items_html = self::render_skill_items( $attrs, $elements, $skills );
		}

		// Content container.
		$content_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__content',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title . $items_html,
			]
		);

		$parent       = BlockParserStore::get_parent( $block->parsed_block['id'], $block->parsed_block['storeInstance'] );
		$parent_attrs = $parent->attrs ?? [];

		return Module::render(
			[
				// FE only.
				'orderIndex'         => $block->parsed_block['orderIndex'],
				'storeInstance'      => $block->parsed_block['storeInstance'],

				// VB equivalent.
				'attrs'              => $attrs,
				'elements'           => $elements,
				'id'                 => $block->parsed_block['id'],
				'name'               => $block->block_type->name,
				'moduleCategory'     => $block->block_type->category,
				'classnamesFunction' => [ SkillsList::class, 'module_classnames' ],
				'stylesComponent'    => [ SkillsList::class, 'module_styles' ],
				'parentAttrs'        => $parent_attrs,
				'parentId'           => $parent->id ?? '',
				'parentName'         => $parent->blockName ?? '',
				'children'           => [
					ElementComponents::component(
						[
							'attrs'         => $attrs['module']['decoration'] ?? [],
							'id'            => $block->parsed_block['id'],
							'orderIndex'    => $block->parsed_block['orderIndex'],
							'storeInstance' => $block->parsed_block['storeInstance'],
						]
					),
					$content_html,
				],
			]
		);
	}

	/**
	 * Render the empty state message.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs    Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 *
	 * @return string HTML for empty state.
	 */
	private static function render_empty_state( array $attrs, $elements ): string {
		$empty_message = $elements->render(
			[
				'attrName' => 'emptyState',
			]
		);

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__empty',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $empty_message,
			]
		);
	}

	/**
	 * Render the skill items grid.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs  Module attributes.
	 * @param ModuleElements $elements ModuleElements instance.
	 * @param array          $skills Skill data.
	 *
	 * @return string HTML for skills grid.
	 */
	private static function render_skill_items( array $attrs, $elements, array $skills ): string {
		// Get visibility toggles.
		$show_description   = $attrs['skills']['advanced']['showDescription']['desktop']['value'] ?? 'on';
		$show_compatibility = $attrs['skills']['advanced']['showCompatibility']['desktop']['value'] ?? 'on';
		$show_version       = $attrs['skills']['advanced']['showVersion']['desktop']['value'] ?? 'on';
		$show_download      = $attrs['skills']['advanced']['showDownloadButton']['desktop']['value'] ?? 'on';

		$items_html = '';

		foreach ( $skills as $skill ) {
			$items_html .= self::render_single_item(
				$attrs,
				$elements,
				$skill,
				$show_description,
				$show_compatibility,
				$show_version,
				$show_download
			);
		}

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__grid',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $items_html,
			]
		);
	}

	/**
	 * Render a single skill item card.
	 *
	 * @since 0.1.0
	 *
	 * @param array          $attrs              Module attributes.
	 * @param ModuleElements $elements           ModuleElements instance.
	 * @param array          $skill              Single skill data.
	 * @param string         $show_description   Show description toggle value.
	 * @param string         $show_compatibility Show compatibility toggle value.
	 * @param string         $show_version       Show version toggle value.
	 * @param string         $show_download      Show download button toggle value.
	 *
	 * @return string HTML for single item card.
	 */
	private static function render_single_item(
		array $attrs,
		$elements,
		array $skill,
		string $show_description,
		string $show_compatibility,
		string $show_version,
		string $show_download
	): string {
		// Item title (skill name).
		$title_html = HTMLUtility::render(
			[
				'tag'               => 'h4',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__item-title',
				],
				'childrenSanitizer' => 'esc_html',
				'children'          => $skill['name'],
			]
		);

		// Badges container (compatibility + version).
		$badges_html = self::render_badges(
			$skill,
			$show_compatibility,
			$show_version
		);

		// Description.
		$description_html = '';
		if ( 'on' === $show_description && ! empty( $skill['description'] ) ) {
			$description_html = HTMLUtility::render(
				[
					'tag'               => 'p',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-skills-list__item-description',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $skill['description'],
				]
			);
		}

		// Header section (title + badges).
		$header_html = HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__item-header',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $title_html . $badges_html,
			]
		);

		// Download button.
		$button_html = '';
		if ( 'on' === $show_download && ! empty( $skill['package_url'] ) ) {
			$button_html = HTMLUtility::render(
				[
					'tag'               => 'div',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-skills-list__item-buttons',
					],
					'childrenSanitizer' => 'et_core_esc_previously',
					'children'          => HTMLUtility::render(
						[
							'tag'               => 'a',
							'tagEscaped'        => true,
							'attributes'        => [
								'href'     => esc_url( $skill['package_url'] ),
								'class'    => 'leaderspath-skills-list__button leaderspath-skills-list__download-button et_pb_button',
								'download' => sanitize_file_name( $skill['name'] . '.zip' ),
							],
							'childrenSanitizer' => 'esc_html',
							'children'          => __( 'Download Skill', 'leaderspath' ),
						]
					),
				]
			);
		}

		// Assemble card.
		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class'         => 'leaderspath-skills-list__item',
					'data-skill-id' => (string) $skill['id'],
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $header_html . $description_html . $button_html,
			]
		);
	}

	/**
	 * Render the badges (compatibility and version) for a skill.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $skill              Skill data.
	 * @param string $show_compatibility Show compatibility toggle value.
	 * @param string $show_version       Show version toggle value.
	 *
	 * @return string HTML for badges container.
	 */
	private static function render_badges(
		array $skill,
		string $show_compatibility,
		string $show_version
	): string {
		$badges = '';

		// Compatibility badge.
		if ( 'on' === $show_compatibility && ! empty( $skill['compatibility'] ) ) {
			$badges .= HTMLUtility::render(
				[
					'tag'               => 'span',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-skills-list__item-badge leaderspath-skills-list__item-badge--compatibility',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => $skill['compatibility'],
				]
			);
		}

		// Version badge.
		if ( 'on' === $show_version && ! empty( $skill['version'] ) ) {
			$badges .= HTMLUtility::render(
				[
					'tag'               => 'span',
					'tagEscaped'        => true,
					'attributes'        => [
						'class' => 'leaderspath-skills-list__item-badge leaderspath-skills-list__item-badge--version',
					],
					'childrenSanitizer' => 'esc_html',
					'children'          => 'v' . $skill['version'],
				]
			);
		}

		if ( empty( $badges ) ) {
			return '';
		}

		return HTMLUtility::render(
			[
				'tag'               => 'div',
				'tagEscaped'        => true,
				'attributes'        => [
					'class' => 'leaderspath-skills-list__item-badges',
				],
				'childrenSanitizer' => 'et_core_esc_previously',
				'children'          => $badges,
			]
		);
	}
}
