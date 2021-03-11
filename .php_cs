<?php

/*
 * This file is part of PHP CS Fixer.
 * (c) Fabien Potencier <fabien@symfony.com>
 *     Dariusz Rumiński <dariusz.ruminski@gmail.com>
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

$header = <<<'EOF'
This file is part of PHP CS Fixer.
(c) Fabien Potencier <fabien@symfony.com>
    Dariusz Rumiński <dariusz.ruminski@gmail.com>
This source file is subject to the MIT license that is bundled
with this source code in the file LICENSE.
EOF;

return PhpCsFixer\Config::create()->setRules(
    [
        '@Symfony'                     => true,
        'phpdoc_no_package'            => false,
        'no_superfluous_phpdoc_tags'   => false,
        'no_spaces_inside_parenthesis' => false,
        'phpdoc_summary'               => false,
        'binary_operator_spaces'       => ['default' => 'align_single_space_minimal'],
        'braces'                       => ['position_after_control_structures' => 'next'],
    ]
)->setIndent('  ');
