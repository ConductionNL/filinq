import React from 'react';
import clsx from 'clsx';
import styles from './styles.module.css';

/**
 * List of features displayed on the homepage
 * Each feature has a title and description
 */
const FeatureList = [
  {
    title: '100% Local Processing',
    description: (
      <>
        All document processing happens within your Nextcloud instance, ensuring your sensitive data never leaves your secure environment while maintaining full functionality.
      </>
    ),
  },
  {
    title: 'Comprehensive Document Services',
    description: (
      <>
        From document generation and signing to GDPR anonymization and WCAG compliance, DocuDesk provides all the tools you need for modern document management.
      </>
    ),
  },
  {
    title: 'Seamless Integration',
    description: (
      <>
        Connect with SharePoint, Office 365, or case management systems while maintaining complete control over your document processing and storage.
      </>
    ),
  },
];

/**
 * Component to render a single feature
 * @param {string} title - The title of the feature
 * @param {JSX.Element} description - The description of the feature
 * @returns {JSX.Element} Feature component
 */
function Feature({title, description}) {
  return (
    <div className={clsx('col col--4')}>
      <div className="text--center padding-horiz--md">
        <h3>{title}</h3>
        <p>{description}</p>
      </div>
    </div>
  );
}

/**
 * Main component that displays all features on the homepage
 * @returns {JSX.Element} HomepageFeatures component
 */
export default function HomepageFeatures() {
  return (
    <section className={styles.features}>
      <div className="container">
        <div className="row">
          {FeatureList.map((props, idx) => (
            <Feature key={idx} {...props} />
          ))}
        </div>
      </div>
    </section>
  );
}