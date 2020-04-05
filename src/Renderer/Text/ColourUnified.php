<?php

declare(strict_types=1);

namespace Jfcherng\Diff\Renderer\Text;

use Jfcherng\Diff\Differ;
use Jfcherng\Diff\SequenceMatcher;
use Console\Decorate;

/**
 * Unified diff generator with colour.
 *
 * @see https://en.wikipedia.org/wiki/Diff#Unified_format
 */
final class ColourUnified extends AbstractText
{
    /**
     * {@inheritdoc}
     */
    const INFO = [
        'desc' => 'Unified',
        'type' => 'Text',
    ];

    /**
     * {@inheritdoc}
     */
    protected function renderWorker(Differ $differ): string
    {
        $ret = '';

        foreach ($differ->getGroupedOpcodesGnu() as $hunk) {
            $ret .= $this->renderHunkHeader($differ, $hunk);
            $ret .= $this->renderHunkBlocks($differ, $hunk);
        }

        return $ret;
    }

    /**
     * Render the hunk header.
     *
     * @param Differ  $differ the differ
     * @param int[][] $hunk   the hunk
     */
    protected function renderHunkHeader(Differ $differ, array $hunk): string
    {
        $lastBlockIdx = \count($hunk) - 1;

        // note that these line number variables are 0-based
        $i1 = $hunk[0][1];
        $i2 = $hunk[$lastBlockIdx][2];
        $j1 = $hunk[0][3];
        $j2 = $hunk[$lastBlockIdx][4];

        $oldLinesCount = $i2 - $i1;
        $newLinesCount = $j2 - $j1;

        return Decorate::color(
            '@@' .
            ' -' .
                // the line number in GNU diff is 1-based, so we add 1
                // a special case is when a hunk has only changed blocks,
                // i.e., context is set to 0, we do not need the adding
                ($i1 === $i2 ? $i1 : $i1 + 1) .
                // if the line counts is 1, it can (and mostly) be omitted
                ($oldLinesCount === 1 ? '' : ",{$oldLinesCount}") .
            ' +' .
                ($j1 === $j2 ? $j1 : $j1 + 1) .
                ($newLinesCount === 1 ? '' : ",{$newLinesCount}") .
            " @@", Decorate::vgaColor(93)) . "\n";
    }

    /**
     * Render the hunk content.
     *
     * @param Differ  $differ the differ
     * @param int[][] $hunk   the hunk
     */
    protected function renderHunkBlocks(Differ $differ, array $hunk): string
    {
        $ret = '';

        $oldNoEolAtEofIdx = $differ->getOldNoEolAtEofIdx();
        $newNoEolAtEofIdx = $differ->getNewNoEolAtEofIdx();

        foreach ($hunk as [$op, $i1, $i2, $j1, $j2]) {
            // note that although we are in a OP_EQ situation,
            // the old and the new may not be exactly the same
            // because of ignoreCase, ignoreWhitespace, etc
            if ($op === SequenceMatcher::OP_EQ) {
                // we could only pick either the old or the new to show
                // here we pick the new one to let the user know what it is now
                $ret .= $this->renderContext(
                    ' ',
                    $differ->getNew($j1, $j2),
                    $j2 === $newNoEolAtEofIdx
                );

                continue;
            }

            if ($op & (SequenceMatcher::OP_REP | SequenceMatcher::OP_DEL)) {
                $ret .= $this->renderContext(
                    '-',
                    $differ->getOld($i1, $i2),
                    $i2 === $oldNoEolAtEofIdx
                );
            }

            if ($op & (SequenceMatcher::OP_REP | SequenceMatcher::OP_INS)) {
                $ret .= $this->renderContext(
                    '+',
                    $differ->getNew($j1, $j2),
                    $j2 === $newNoEolAtEofIdx
                );
            }
        }

        return $ret;
    }

    /**
     * Render the context array with the symbol.
     *
     * @param string   $symbol     the symbol
     * @param string[] $context    the context
     * @param bool     $noEolAtEof there is no EOL at EOF in this block
     */
    protected function renderContext(string $symbol, array $context, bool $noEolAtEof = false): string
    {
        if (empty($context)) {
            return '';
        }

        switch($symbol) {
            case '-':
                $context = array_map(function($c) use ($symbol) {
                    return Decorate::color($symbol . $c, [
                        Decorate::vgaColor(52),
                        Decorate::vgaBackground(9)
                    ]);
                }, $context);
                $ret = \implode("\n", $context) . "\n";
                break;
            case '+':
                $context = array_map(function($c) use ($symbol) {
                    return Decorate::color($symbol . $c, [
                        Decorate::vgaColor(22),
                        Decorate::vgaBackground(120)
                    ]);
                }, $context);
                $ret = \implode("\n", $context) . "\n";
                break;
            default:
                $ret = $symbol . \implode("\n{$symbol}", $context) . "\n";
                break;
        }

        if ($noEolAtEof) {
            $ret .= self::GNU_OUTPUT_NO_EOL_AT_EOF . "\n";
        }

        return $ret;
    }
}
