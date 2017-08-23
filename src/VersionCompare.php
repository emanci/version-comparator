<?php

namespace Emanci\VersionCompare;

/**
 * 按照"语义化版本控制规范"比较版本号.
 *
 * @link http://semver.org Semantic Versioning
 */
class VersionCompare
{
    /**
     * The Version instance.
     *
     * @var Version
     */
    protected $version;

    /**
     * Can use the comparison operator.
     * <、 lt、<=、 le、>、 gt、>=、 ge、==、 =、eq、 !=、<> 和 ne.
     *
     * @var array
     */
    protected $compOperators = [
        -1 => ['<', 'lt', '<=', 'le', '!=', '<>', 'ne'],
        0 => ['==', '=', 'eq'],
        1 => ['>', 'gt', '>=', 'ge', '!=', '<>', 'ne'],
    ];

    /**
     * VersionCompare construct.
     *
     * @param string $versionStr
     */
    public function __construct($versionStr = null)
    {
        $this->version = new Version();

        if ($versionStr && is_string($versionStr)) {
            $this->parseVersion($versionStr);
        }
    }

    /**
     * Set version.
     *
     * @param Version $version
     *
     * @return VersionCompare
     */
    public function setVersion(Version $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Get version.
     *
     * @return Version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * Creates a new Version instance.
     *
     * @param string $versionStr
     *
     * @return VersionCompare
     */
    protected static function create($versionStr)
    {
        return new static($versionStr);
    }

    /**
     * Checks if the string is a valid string representation of a version.
     *
     * @param string $string
     *
     * @return bool
     */
    public static function checkValid($string)
    {
        return true;
    }

    /**
     * Compares one version string to another.
     *
     * @param string $versionStr
     *
     * @return bool
     */
    public function compareTo($versionStr)
    {
    }

    /**
     * Compares two "PHP-standardized" version number strings.
     *
     * @param string $version1
     * @param string $operator
     * @param string $version2
     *
     * @return bool
     */
    public function compare($version1, $operator, $version2)
    {
        $version1 = static::create($version1)->getVersion();
        $version2 = static::create($version2)->getVersion();
        $comp = $this->execCompareTask($version1, $version2);

        return $this->showResult($comp, $operator);
    }

    protected function execCompareTask()
    {
        $comp = 0;
        $compareTasks = ['commonTask', 'preReleaseTask', 'buildMetadataTask'];

        array_walk($compareTasks, function ($value) use (&$comp) {
            if (!$comp) {
                $comp = call_user_func([$this, $value]);
            }
        });

        return $comp;
    }

    protected function showResult($comp, $operator)
    {
    }

    protected function commonTask($version1, $version2)
    {
        // 1.0.0 < 2.0.0 < 2.1.0 < 2.1.1
        $version1Str = strval($version1->getMajor().$version1->getMinor().$version1->getPatch());
        $version2Str = strval($version2->getMajor().$version2->getMinor().$version2->getPatch());

        return version_compare($version1Str, $version2Str);
    }

    protected function preReleaseTask($version1, $version2)
    {
        // 1.0.0-alpha < 1.0.0
        if ($preRelease1 = $version1->getPreRelease() ||
            $preRelease2 = $version2->getPreRelease()) {
            if ($preRelease1 && !$preRelease2) {
                return -1;
            }

            if (!$preRelease1 && $preRelease2) {
                return 1;
            }

            // 0
        }
    }

    protected function buildMetadataTask($version1, $version2)
    {
        if ($buildMetadata1 = $version1->getBuildMetadata() ||
            $buildMetadata2 = $version2->getBuildMetadata()) {
            if ($buildMetadata1 && !$buildMetadata2) {
                return -1;
            }

            if (!$buildMetadata1 && $buildMetadata2) {
                return 1;
            }

            // 0
        }
    }

    /**
     * Parses the version string.
     *
     * @param string $versionStr
     */
    protected function parseVersion($versionStr)
    {
        if (false !== strpos($versionStr, '+')) {
            list($versionStr, $buildMetadata) = explode('+', $versionStr);
            $buildMetadata = explode('.', $buildMetadata);
            $this->version->setBuildMetadata($buildMetadata);
        }

        if (false !== ($pos = strpos($versionStr, '-'))) {
            $original = $versionStr;
            $versionStr = substr($versionStr, 0, $pos);
            $preRelease = explode('.', substr($original, $pos + 1));
            $this->version->setPreRelease($preRelease);
        }

        $versions = explode('.', $versionStr);

        $this->version->setMajor((int) $versions[0]);

        if (isset($versions[1])) {
            $this->version->setMinor((int) $versions[1]);
        }

        if (isset($versions[2])) {
            $this->version->setPatch((int) $versions[2]);
        }
    }
}
