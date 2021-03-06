<?php

namespace Oro\Bundle\AddressBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\AddressBundle\Entity\Address;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganization;
use Oro\Bundle\AddressBundle\Validator\Constraints\NameOrOrganizationValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class NameOrOrganizationValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var NameOrOrganization */
    private $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ExecutionContextInterface */
    private $context;

    /** @var NameOrOrganizationValidator */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->constraint = new NameOrOrganization();
        $this->validator = new NameOrOrganizationValidator();
        $this->validator->initialize($this->context);
    }

    public function testConfiguration()
    {
        $this->assertEquals(NameOrOrganizationValidator::class, $this->constraint->validatedBy());

        $this->assertEquals(Constraint::CLASS_CONSTRAINT, $this->constraint->getTargets());
    }

    /**
     * @expectedException \Symfony\Component\Validator\Exception\UnexpectedTypeException
     */
    public function testNotQuoteProduct()
    {
        $this->validator->validate(new \stdClass(), $this->constraint);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param mixed $data
     * @param boolean $valid
     */
    public function testValidate($data, $valid)
    {
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($valid ? $this->never() : $this->at(0))
            ->method('buildViolation')
            ->with($this->constraint->firstNameMessage)
            ->willReturn($builder);
        $builder->expects($valid ? $this->never() : $this->once())
            ->method('atPath')
            ->with('firstName')
            ->willReturnSelf();
        $builder->expects($valid ? $this->never() : $this->once())
            ->method('addViolation');

        $builder2 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($valid ? $this->never() : $this->at(1))
            ->method('buildViolation')
            ->with($this->constraint->lastNameMessage)
            ->willReturn($builder2);
        $builder2->expects($valid ? $this->never() : $this->once())
            ->method('atPath')
            ->with('lastName')
            ->willReturnSelf();
        $builder2->expects($valid ? $this->never() : $this->once())
            ->method('addViolation');

        $builder3 = $this->createMock(ConstraintViolationBuilderInterface::class);
        $this->context->expects($valid ? $this->never() : $this->at(2))
            ->method('buildViolation')
            ->with($this->constraint->organizationMessage)
            ->willReturn($builder3);
        $builder3->expects($valid ? $this->never() : $this->once())
            ->method('atPath')
            ->with('organization')
            ->willReturnSelf();
        $builder3->expects($valid ? $this->never() : $this->once())
            ->method('addViolation');

        $this->validator->validate($data, $this->constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            'empty' => [
                'data' => new Address(),
                'valid' => false,
            ],
            'empty first name' => [
                'data' => (new Address())->setLastName('test last name')->setOrganization('test organization'),
                'valid' => true,
            ],
            'empty last name' => [
                'data' => (new Address())->setFirstName('test first name')->setOrganization('test organization'),
                'valid' => true,
            ],
            'empty organization' => [
                'data' => (new Address())->setFirstName('test first name')->setLastName('test last name'),
                'valid' => true,
            ],
            'empty first name and organization' => [
                'data' => (new Address())->setLastName('test last name'),
                'valid' => false,
            ],
            'empty last name and organization' => [
                'data' => (new Address())->setFirstName('test first name'),
                'valid' => false,
            ],
            'empty first name and last name' => [
                'data' => (new Address())->setOrganization('test organization'),
                'valid' => true,
            ],
            'filled' => [
                'data' => (new Address())
                    ->setFirstName('test first name')
                    ->setLastName('test last name')
                    ->setOrganization('test organization'),
                'valid' => true,
            ],
        ];
    }
}
