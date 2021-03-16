import React from 'react';
import Layout from '@theme/Layout';
import Link from '@docusaurus/Link';
import useDocusaurusContext from '@docusaurus/useDocusaurusContext';
import useBaseUrl from '@docusaurus/useBaseUrl';
import styles from './styles.module.css';

export default function Home() {
    const context = useDocusaurusContext();
    const { siteConfig = {} } = context;
    return (
        <Layout title={`Hello from ${siteConfig.title}`}>
            <main className={styles.content}>
                <div className={styles.hero}>
                    <h1 className={styles.title}>{siteConfig.title}</h1>
                    <p className={styles.tagline}>{siteConfig.tagline}</p>

                    <div className={styles.buttons}>
                        <Link className={styles.button} to={useBaseUrl('docs')}>
                            Get started
                        </Link>
                    </div>
                </div>
            </main>
        </Layout>
    );
}
