<?php

namespace Camilo3rd\LaravelPostman;

use phpDocumentor\Reflection\DocBlock\Description;
use phpDocumentor\Reflection\DocBlock\DescriptionFactory;
use phpDocumentor\Reflection\DocBlock\Tags\BaseTag;
use phpDocumentor\Reflection\Types\Context;
use Webmozart\Assert\Assert;

final class RouteNameTag extends BaseTag
{

    protected string $name = 'route-name';

    /**
     * The constructor for this Tag; this should contain all properties for this object.
     *
     * @param Description $description An example of how to add a Description to the tag; the Description is often
     *                                 an optional variable so passing null is allowed in this instance (though you can
     *                                 also construct an empty description object).
     *
     * @see BaseTag for the declaration of the description property and getDescription method.
     */
    public function __construct(Description $description = null)
    {
        $this->description = $description;
    }

    /**
     * A static Factory that creates a new instance of the current Tag.
     *
     * In this example the MyTag tag can be created by passing a description text as $body. Because we have added
     * a $descriptionFactory that is type-hinted as DescriptionFactory we can now construct a new Description object
     * and pass that to the constructor.
     *
     * > You could directly instantiate a Description object here but that won't be parsed for inline tags and Types
     * > won't be resolved. The DescriptionFactory will take care of those actions.
     *
     * The `create` method's interface states that this method only features a single parameter (`$body`) but the
     * {@see TagFactory} will read the signature of this method and if it has more parameters then it will try
     * to find declarations for it in the ServiceLocator of the TagFactory (see {@see TagFactory::$serviceLocator}).
     *
     * > Important: all properties following the `$body` should default to `null`, otherwise PHP will error because
     * > it no longer matches the interface. This is why you often see the default tags check that an optional argument
     * > is not null nonetheless.
     *
     * @param string             $body
     * @param DescriptionFactory $descriptionFactory
     * @param Context|null       $context            The Context is used to resolve Types and FQSENs, although optional
     *                                               it is highly recommended to pass it. If you omit it then it is
     *                                               assumed that the DocBlock is in the global namespace and has no
     *                                               `use` statements.
     *
     * @see Tag for the interface declaration of the `create` method.
     * @see Tag::create() for more information on this method's workings.
     */
    public static function create(string $body, DescriptionFactory $descriptionFactory = null, Context $context = null): self
    {
        Assert::notNull($descriptionFactory);

        return new static($descriptionFactory->create($body, $context));
    }

    /**
     * Returns a rendition of the original tag line.
     *
     * This method is used to reconstitute a DocBlock into its original form by the {@see Serializer}. It should
     * feature all parts of the tag so that the serializer can put it back together.
     */
    public function __toString(): string
    {
        return (string)$this->description;
    }
}
