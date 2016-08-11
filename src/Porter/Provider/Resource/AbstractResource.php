<?php
namespace ScriptFUSION\Porter\Provider\Resource;

/**
 * Partially implements Resource.
 */
abstract class AbstractResource implements Resource
{
    /** @var string */
    private $providerTag;

    /**
     * {@inheritdoc}
     *
     * @return string Provider tag.
     */
    public function getProviderTag()
    {
        return $this->providerTag;
    }

    /**
     * Sets the provider identifier tag.
     *
     * @param string $tag Provider tag.
     *
     * @return $this
     */
    public function setProviderTag($tag)
    {
        $this->providerTag = "$tag";

        return $this;
    }
}
