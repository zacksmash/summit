import stylistic from '@stylistic/eslint-plugin';
import { defineConfigWithVueTs, vueTsConfigs } from '@vue/eslint-config-typescript';
import betterTailwindcss from 'eslint-plugin-better-tailwindcss';
import importPlugin from 'eslint-plugin-import';
import vue from 'eslint-plugin-vue';

const controlStatements = [
    'if',
    'return',
    'for',
    'while',
    'do',
    'switch',
    'try',
    'throw',
];
const paddingAroundControl = [
    ...controlStatements.flatMap((stmt) => [
        { blankLine: 'always', prev: '*', next: stmt },
        { blankLine: 'always', prev: stmt, next: '*' },
    ]),
];

export default defineConfigWithVueTs(
    vue.configs['flat/essential'],
    vueTsConfigs.recommended,
    betterTailwindcss.configs['recommended'],
    {
        plugins: {
            import: importPlugin,
        },
        settings: {
            'import/resolver': {
                typescript: {
                    alwaysTryTypes: true,
                    project: './tsconfig.json',
                },
                node: true,
            },
            'better-tailwindcss': {
                entryPoint: 'resources/css/app.css',
            },
        },
        rules: {
            'vue/attributes-order': [
                'error',
                {
                    alphabetical: false,
                },
            ],
            'vue/block-lang': 'off',
            'vue/block-order': ['error', {
                order: ['script', 'template', 'style'],
            }],
            'vue/first-attribute-linebreak': 'error',
            'vue/html-closing-bracket-newline': 'error',
            'vue/html-closing-bracket-spacing': [
                'error',
                { selfClosingTag: 'never' },
            ],
            'vue/html-indent': ['error', 4],
            'vue/html-self-closing': ['warn', {
                html: {
                    normal: 'any',
                    component: 'any',
                },
            }],
            'vue/max-attributes-per-line': [
                'error',
                {
                    singleline: 2,
                    multiline: 1,
                },
            ],
            'vue/multi-word-component-names': 'off',
            'vue/multiline-html-element-content-newline': 'error',
            'vue/no-reserved-component-names': 'off',
            'vue/no-undef-components': 'off',
            'vue/no-v-text-v-html-on-component': 'off',
            'vue/order-in-components': [
                'error',
                {
                    order: [
                        'name',
                        'layout',
                        'parent',
                        ['components', 'directives', 'filters'],
                        'extends',
                        'mixins',
                        ['provide', 'inject'],
                        ['props', 'propsData'],
                        'emits',
                        'data',
                        'computed',
                        'watch',
                        'watchQuery',
                        'methods',
                        'LIFECYCLE_HOOKS',
                    ],
                },
            ],
            'vue/padding-lines-in-component-definition': ['error', {
                betweenOptions: 'always',
                withinOption: {
                    props: {
                        betweenItems: 'never',
                        withinEach: 'never',
                    },
                    data: {
                        betweenItems: 'never',
                        withinEach: 'never',
                    },
                },
                groupSingleLineProperties: false,
            }],
            'vue/script-indent': ['error', 4, { switchCase: 1 }],
            '@typescript-eslint/no-explicit-any': 'off',
            '@typescript-eslint/no-unused-vars': 'off',
            '@typescript-eslint/consistent-type-imports': [
                'error',
                {
                    prefer: 'type-imports',
                    fixStyle: 'separate-type-imports',
                },
            ],
            'import/order': [
                'error',
                {
                    groups: ['builtin', 'external', 'internal', 'parent', 'sibling', 'index'],
                    alphabetize: {
                        order: 'asc',
                        caseInsensitive: true,
                    },
                },
            ],
            'import/consistent-type-specifier-style': [
                'error',
                'prefer-top-level',
            ],
            'object-curly-newline': 'error',
            'object-property-newline': ['error', {
                allowAllPropertiesOnSameLine: true,
            }],
            'sort-imports': [
                'error',
                {
                    ignoreDeclarationSort: true,
                    allowSeparatedGroups: true,
                },
            ],
        },
    },
    {
        plugins: {
            '@stylistic': stylistic,
        },
        rules: {
            // Formatting
            '@stylistic/semi': ['error', 'always'],
            '@stylistic/quotes': ['error', 'single', { avoidEscape: true }],
            '@stylistic/indent': ['error', 4, { SwitchCase: 1 }],
            '@stylistic/comma-dangle': ['error', 'always-multiline'],
            '@stylistic/arrow-parens': ['error', 'always'],
            '@stylistic/object-curly-spacing': ['error', 'always'],
            '@stylistic/linebreak-style': ['error', 'unix'],
            '@stylistic/quote-props': ['error', 'as-needed'],
            '@stylistic/eol-last': ['error', 'always'],
            '@stylistic/no-trailing-spaces': 'error',
            '@stylistic/no-multiple-empty-lines': ['error', { max: 1 }],
            '@stylistic/no-multi-spaces': 'error',
            '@stylistic/comma-spacing': ['error', { before: false, after: true }],
            '@stylistic/key-spacing': ['error', { beforeColon: false, afterColon: true }],
            '@stylistic/space-before-blocks': 'error',
            '@stylistic/keyword-spacing': ['error', { before: true, after: true }],
            '@stylistic/space-infix-ops': 'error',
            '@stylistic/block-spacing': ['error', 'always'],
            '@stylistic/space-before-function-paren': ['error', {
                anonymous: 'always',
                named: 'never',
                asyncArrow: 'always',
            }],

            // Code style
            curly: ['error', 'all'],
            '@stylistic/brace-style': ['error', '1tbs', { allowSingleLine: false }],
            '@stylistic/padding-line-between-statements': [
                'error',
                ...paddingAroundControl,
            ],
        },
    },
    {
        ignores: [
            '.ai/*',
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'vite.config.ts',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
            'resources/views/mail/*',
        ],
    },
);
