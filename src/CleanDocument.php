<?php

namespace ByJG\XmlUtil;

class CleanDocument
{
    protected string $document;

    public function __construct(string $document)
    {
        $this->document = $document;
    }

    public function stripAllTags(): string
    {
        return strip_tags($this->document);
    }

    /**
     * @param array $allowedTags
     * @return $this
     */
    public function stripTagsExcept(array $allowedTags): self
    {
        $this->document = strip_tags($this->document, '<' . implode('><', $allowedTags) . '>');
        return $this;
    }

    /**
     * @param string $property
     * @return $this
     */
    public function removeContentByProperty(string $property): self
    {
        $this->removeContentByTag('\w', $property);
        return $this;
    }

    /**
     * @param string $tag
     * @param string $property
     * @return $this
     */
    public function removeContentByTag(string $tag, string $property = ''): self
    {
        $pattern = '~<(' . $tag . ')\b\s[^>]*>.*?</\1>~';
        if (!empty($property)) {
            $pattern = '~<(' . $tag . ')\b\s[^>]*' . $property . '\s*?=?[^>]*>.*?</\1>~';
        }
        $this->document = preg_replace($pattern, '', $this->document);
        return $this;
    }

    /**
     * @param string $tag
     * @param string $property
     * @return $this
     */
    public function removeContentByTagWithoutProperty(string $tag, string $property): self
    {
        $pattern = '~<(' . $tag . ')\b\s(?![^>]*' . $property . '\s*?=?)[^>]*>.*?</\1>~';
        $this->document = preg_replace($pattern, '', $this->document);
        return $this;
    }

    public function get(): string
    {
        return $this->document;
    }
}
