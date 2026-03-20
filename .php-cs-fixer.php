<?php

return (new PhpCsFixer\Config())
    ->setParallelConfig(PhpCsFixer\Runner\Parallel\ParallelConfigFactory::detect())
    ->setUsingCache(false)
    ->setRiskyAllowed(true)
    ->setLineEnding("\n")
    ->setRules(
        [
            '@PSR1'           => true,
            '@PSR2'           => true,
            '@Symfony'        => true,
            'psr_autoloading' => true,
            // custom rules
            'align_multiline_comment'    => ['comment_type' => 'phpdocs_only'], // psr-5
            'phpdoc_to_comment'          => false,
            'no_superfluous_phpdoc_tags' => false,
            'array_indentation'          => true,
            'array_syntax'               => ['syntax' => 'short'],
            'binary_operator_spaces'     => ['operators' => [
                '='  => 'align',
                '=>' => 'align',
            ]],
            'cast_spaces'                         => ['space' => 'none'],
            'concat_space'                        => ['spacing' => 'one'],
            'compact_nullable_type_declaration'   => true,
            'declare_equal_normalize'             => ['space' => 'none'],
            'increment_style'                     => ['style' => 'post'],
            'list_syntax'                         => ['syntax' => 'short'],
            'echo_tag_syntax'                     => ['format' => 'long'],
            'phpdoc_add_missing_param_annotation' => ['only_untyped' => true],
            'phpdoc_align'                        => false,
            'phpdoc_no_empty_return'              => false,
            'phpdoc_order'                        => true, // psr-5
            'phpdoc_no_useless_inheritdoc'        => false,
            'protected_to_private'                => false,
            'yoda_style'                          => false,
            'method_argument_space'               => ['on_multiline' => 'ensure_fully_multiline'],
            'ordered_imports'                     => [
                'sort_algorithm' => 'alpha',
                'imports_order'  => ['class', 'const', 'function'],
            ],
            'single_line_throw' => false,
        ]
    )
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in(__DIR__ . '/app')
            ->in(__DIR__ . '/tests')
            ->in(__DIR__ . '/config')
            ->name('*.php')
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
    );
