<?php

/**
 * Class representing a 'diff' between two sequences of strings.
 *
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class Jp7_Diff
{
    public $edits;

    /**
     * Constructor.
     * Computes diff between sequences of strings.
     *
     * @param $from_lines array An array of strings.
     *		  (Typically these are lines from a file.)
     * @param $to_lines array An array of strings.
     */
    public function __construct($from_lines, $to_lines)
    {
        $eng = new Jp7_Diff_Engine();
        $this->edits = $eng->diff($from_lines, $to_lines);
        //$this->_check($from_lines, $to_lines);
    }

    /**
     * Compute reversed Diff.
     *
     * SYNOPSIS:
     *
     *	$diff = new Diff($lines1, $lines2);
     *	$rev = $diff->reverse();
     *
     * @return object A Diff object representing the inverse of the
     *                original diff.
     */
    public function reverse()
    {
        $rev = $this;
        $rev->edits = [];
        foreach ($this->edits as $edit) {
            $rev->edits[] = $edit->reverse();
        }

        return $rev;
    }

    /**
     * Check for empty diff.
     *
     * @return bool True iff two sequences were identical.
     */
    public function isEmpty()
    {
        foreach ($this->edits as $edit) {
            if ($edit->type != 'copy') {
                return false;
            }
        }

        return true;
    }

    /**
     * Compute the length of the Longest Common Subsequence (LCS).
     *
     * This is mostly for diagnostic purposed.
     *
     * @return int The length of the LCS.
     */
    public function lcs()
    {
        $lcs = 0;
        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $lcs += sizeof($edit->orig);
            }
        }

        return $lcs;
    }

    /**
     * Get the original set of lines.
     *
     * This reconstructs the $from_lines parameter passed to the
     * constructor.
     *
     * @return array The original sequence of strings.
     */
    public function orig()
    {
        $lines = [];

        foreach ($this->edits as $edit) {
            if ($edit->orig) {
                array_splice($lines, sizeof($lines), 0, $edit->orig);
            }
        }

        return $lines;
    }

    /**
     * Get the closing set of lines.
     *
     * This reconstructs the $to_lines parameter passed to the
     * constructor.
     *
     * @return array The sequence of strings.
     */
    public function closing()
    {
        $lines = [];

        foreach ($this->edits as $edit) {
            if ($edit->closing) {
                array_splice($lines, sizeof($lines), 0, $edit->closing);
            }
        }

        return $lines;
    }

    /**
     * Check a Diff for validity.
     *
     * This is here only for debugging purposes.
     */
    public function _check($from_lines, $to_lines)
    {
        //wfProfileIn( __METHOD__ );
        if (serialize($from_lines) != serialize($this->orig())) {
            trigger_error("Reconstructed original doesn't match", E_USER_ERROR);
        }
        if (serialize($to_lines) != serialize($this->closing())) {
            trigger_error("Reconstructed closing doesn't match", E_USER_ERROR);
        }

        $rev = $this->reverse();
        if (serialize($to_lines) != serialize($rev->orig())) {
            trigger_error("Reversed original doesn't match", E_USER_ERROR);
        }
        if (serialize($from_lines) != serialize($rev->closing())) {
            trigger_error("Reversed closing doesn't match", E_USER_ERROR);
        }

        $prevtype = 'none';
        foreach ($this->edits as $edit) {
            if ($prevtype == $edit->type) {
                trigger_error('Edit sequence is non-optimal', E_USER_ERROR);
            }
            $prevtype = $edit->type;
        }

        $lcs = $this->lcs();
        trigger_error('Diff okay: LCS = '.$lcs, E_USER_NOTICE);
        //wfProfileOut( __METHOD__ );
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _HWLDF_WordAccumulator
{

    private $_lines;
    private $_line;
    private $_group;
    private $_tag;

    public function _HWLDF_WordAccumulator()
    {
        $this->_lines = [];
        $this->_line = '';
        $this->_group = '';
        $this->_tag = '';
    }

    public function _flushGroup($new_tag)
    {
        if ($this->_group !== '') {
            if ($this->_tag == 'ins') {
                $this->_line .= '<ins class="diffchange diffchange-inline">'.
            htmlspecialchars($this->_group).'</ins>';
            } elseif ($this->_tag == 'del') {
                $this->_line .= '<del class="diffchange diffchange-inline">'.
            htmlspecialchars($this->_group).'</del>';
            } else {
                $this->_line .= htmlspecialchars($this->_group);
            }
        }
        $this->_group = '';
        $this->_tag = $new_tag;
    }

    public function _flushLine($new_tag)
    {
        $this->_flushGroup($new_tag);
        if ($this->_line != '') {
            array_push($this->_lines, $this->_line);
        } else {
            # make empty lines visible by inserting an NBSP
        array_push($this->_lines, ' ');
        }
        $this->_line = '';
    }

    public function addWords($words, $tag = '')
    {
        if ($tag != $this->_tag) {
            $this->_flushGroup($tag);
        }

        foreach ($words as $word) {
            // new-line should only come as first char of word.
            if ($word == '') {
                continue;
            }
            if ($word[0] == "\n") {
                $this->_flushLine($tag);
                $word = mb_substr($word, 1);
            }
            assert(!strstr($word, "\n"));
            $this->_group .= $word;
        }
    }

    public function getLines()
    {
        $this->_flushLine('~done');

        return $this->_lines;
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _DiffOp
{
    public $type;
    public $orig;
    public $closing;

    public function reverse()
    {
        trigger_error('pure virtual', E_USER_ERROR);
    }

    public function norig()
    {
        return $this->orig ? sizeof($this->orig) : 0;
    }

    public function nclosing()
    {
        return $this->closing ? sizeof($this->closing) : 0;
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _DiffOp_Copy extends _DiffOp
{
    public $type = 'copy';

    public function _DiffOp_Copy($orig, $closing = false)
    {
        if (!is_array($closing)) {
            $closing = $orig;
        }
        $this->orig = $orig;
        $this->closing = $closing;
    }

    public function reverse()
    {
        return new self($this->closing, $this->orig);
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _DiffOp_Delete extends _DiffOp
{
    public $type = 'delete';

    public function _DiffOp_Delete($lines)
    {
        $this->orig = $lines;
        $this->closing = false;
    }

    public function reverse()
    {
        return new _DiffOp_Add($this->orig);
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _DiffOp_Add extends _DiffOp
{
    public $type = 'add';

    public function _DiffOp_Add($lines)
    {
        $this->closing = $lines;
        $this->orig = false;
    }

    public function reverse()
    {
        return new _DiffOp_Delete($this->closing);
    }
}

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class _DiffOp_Change extends _DiffOp
{
    public $type = 'change';

    public function _DiffOp_Change($orig, $closing)
    {
        $this->orig = $orig;
        $this->closing = $closing;
    }

    public function reverse()
    {
        return new self($this->closing, $this->orig);
    }
}
