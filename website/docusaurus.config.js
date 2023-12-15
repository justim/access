/** @type {import('@docusaurus/types').DocusaurusConfig} */

import { themes } from 'prism-react-renderer';

export default {
    title: 'Access',
    tagline: ' A simple MySQL wrapper optimized for bigger data sets ',
    url: 'https://access.justim.net',
    baseUrl: '/',
    onBrokenLinks: 'throw',
    onBrokenMarkdownLinks: 'warn',
    favicon: 'images/favicon.ico',
    organizationName: 'justim', // Usually your GitHub org/user name.
    projectName: 'access', // Usually your repo name.
    trailingSlash: false,
    themeConfig: {
        colorMode: {
            respectPrefersColorScheme: true,
        },
        navbar: {
            title: 'Access',
            logo: {
                src: 'images/access.png',
                alt: 'Access logo',
            },
            items: [
                {
                    to: 'docs',
                    activeBasePath: 'docs',
                    label: 'Docs',
                    position: 'left',
                },
                {
                    href: 'https://github.com/justim/access',
                    label: 'GitHub',
                    position: 'right',
                },
            ],
        },
        footer: {
            style: 'light',
            copyright: `Copyright © ${new Date().getFullYear()} Access`,
        },
        prism: {
            theme: themes.okaidia,
            additionalLanguages: ['php'],
        },
    },
    presets: [
        [
            '@docusaurus/preset-classic',
            {
                docs: {
                    path: '../docs',
                    sidebarPath: require.resolve('./sidebars.js'),
                    editUrl: 'https://github.com/justim/access/edit/master/website/',
                },
                blog: {
                    feedOptions: {
                        type: null,
                    },
                },
                theme: {
                    customCss: require.resolve('./src/css/custom.css'),
                },
            },
        ],
    ],
};
