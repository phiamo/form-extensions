<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Form\Tests\Type;

use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Sonata\Form\Date\MomentFormatConverter;
use Sonata\Form\Tests\Fixtures\Type\DummyPickerType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @author Hugo Briand <briand@ekino.com>
 */
final class BasePickerTypeTest extends TestCase
{
    /**
     * @var Stub&TranslatorInterface
     */
    private $translator;

    /**
     * @var MomentFormatConverter
     */
    private $momentFormatConverter;

    protected function setUp(): void
    {
        parent::setUp();

        $this->momentFormatConverter = new MomentFormatConverter();
        $this->translator = $this->createStub(TranslatorInterface::class);
    }

    /**
     * @dataProvider provideTypeOptions
     */
    public function testFinishView(array $expectedOptions, array $options): void
    {
        $type = new DummyPickerType(
            $this->momentFormatConverter,
            $this->translator,
            'en'
        );

        $view = new FormView();
        $form = new Form($this->createStub(FormConfigInterface::class));

        $type->finishView($view, $form, $options);

        $this->assertArrayHasKey('moment_format', $view->vars);
        $this->assertArrayHasKey('dp_options', $view->vars);
        $this->assertArrayHasKey('datepicker_use_button', $view->vars);
        $this->assertSame($expectedOptions['minDate'], $view->vars['dp_options']['minDate']);
        $this->assertSame($expectedOptions['maxDate'], $view->vars['dp_options']['maxDate']);

        if (true === $expectedOptions['useSeconds']) {
            $this->assertTrue($view->vars['dp_options']['useSeconds']);
        } elseif (false === $expectedOptions['useSeconds']) {
            $this->assertFalse($view->vars['dp_options']['useSeconds']);
        }

        foreach ($view->vars['dp_options'] as $dpKey => $dpValue) {
            $this->assertFalse(strpos($dpKey, '_'));
            $this->assertFalse(strpos($dpKey, 'dp_'));
        }

        $this->assertSame('text', $view->vars['type']);
    }

    /**
     * @dataProvider provideTypeOptions
     */
    public function testTimePickerIntlFormater(array $expectedOptions, array $options): void
    {
        $type = new DummyPickerType(
            $this->momentFormatConverter,
            $this->translator,
            'en'
        );

        $view = new FormView();
        $form = new Form($this->createStub(FormConfigInterface::class));

        $type->finishView($view, $form, $options);

        if (true === $expectedOptions['useSeconds']) {
            $this->assertTrue($view->vars['dp_options']['useSeconds']);
        } elseif (false === $expectedOptions['useSeconds']) {
            $this->assertFalse($view->vars['dp_options']['useSeconds']);
        }

        $this->assertSame($expectedOptions['moment_format'], $view->vars['moment_format']);
        $this->assertSame($expectedOptions['maxDate'], $view->vars['dp_options']['maxDate']);
    }

    public function provideTypeOptions(): iterable
    {
        yield [
            [
                'moment_format' => 'H:mm',
                'minDate' => '1/1/1900',
                'maxDate' => '0:00',
                'useSeconds' => false,
            ],
            [
                'format' => 'H:mm',
                'dp_min_date' => '1/1/1900',
                'dp_max_date' => new \DateTime('3/1/2001'),
                'dp_pick_time' => true,
                'dp_pick_date' => false,
            ],
        ];

        yield [
            [
                'moment_format' => 'YYYY-MM-DD',
                'minDate' => '1/1/1900',
                'maxDate' => '2001-01-01',
                'useSeconds' => false,
            ],
            [
                'html5' => false,
                'format' => 'yyyy-MM-dd',
                'dp_min_date' => '1/1/1900',
                'dp_max_date' => new \DateTime('1/1/2001'),
                'dp_use_seconds' => true,
            ],
        ];

        yield [
            [
                'moment_format' => 'H:mm',
                'minDate' => '1/1/1900',
                'maxDate' => '0:00',
                'useSeconds' => false,
            ],
            [
                'format' => 'H:mm',
                'dp_min_date' => '1/1/1900',
                'dp_max_date' => new \DateTimeImmutable('7/10/2016'),
                'dp_pick_time' => true,
                'dp_pick_date' => false,
            ],
        ];
    }

    public function testTimePickerUsesDefaultLocaleWithoutRequest(): void
    {
        $type = new DummyPickerType(
            $this->momentFormatConverter,
            $this->translator,
            'en'
        );

        $this->assertSame('en', $type->getLocale());
    }

    /**
     * @group legacy
     */
    public function testConstructWithRequestStack(): void
    {
        $type = new DummyPickerType(
            $this->momentFormatConverter,
            $this->translator,
            $this->getRequestStack()
        );

        $this->assertSame('en', $type->getLocale());
    }

    private function getRequestStack(string $locale = 'en'): RequestStack
    {
        $requestStack = new RequestStack();
        $request = $this->createStub(Request::class);
        $request
            ->method('getLocale')
            ->willReturn($locale);
        $requestStack->push($request);

        return $requestStack;
    }
}
