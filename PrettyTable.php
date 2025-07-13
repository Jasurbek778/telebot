<?php

class HRuleStyle {
    const FRAME = 0;
    const ALL = 1;
    const NONE = 2;
    const HEADER = 3;
}

class VRuleStyle {
    const FRAME = 0;
    const ALL = 1;
    const NONE = 2;
}

class PrettyTable {
    private $field_names = [];
    private $rows = [];
    private $align = [];
    private $max_width = [];
    private $border = true;
    private $hrules = HRuleStyle::FRAME;
    private $vrules = VRuleStyle::ALL;
    private $padding_width = 1;
    private $left_padding_width = null;
    private $right_padding_width = null;
    private $vertical_char = '|';
    private $horizontal_char = '-';
    private $junction_char = '+';
    private $int_format = [];
    private $float_format = [];
    private $none_format = [];

    /**
     * Constructor to initialize field names
     * @param array $field_names Array of column headers
     */
    public function __construct($field_names = []) {
        $this->setFieldNames($field_names);
    }

    /**
     * Set field names for the table
     * @param array $field_names Array of column headers
     * @throws Exception if field names are invalid
     */
    public function setFieldNames($field_names) {
        if (!empty($this->rows) && count($field_names) != count($this->rows[0])) {
            throw new Exception("Field name list has incorrect number of values, (actual) " . count($field_names) . "!=" . count($this->rows[0]) . " (expected)");
        }
        if (count($field_names) != count(array_unique($field_names))) {
            throw new Exception("Field names must be unique");
        }
        $this->field_names = $field_names;
        $this->align = [];
        $this->max_width = [];
        $this->int_format = [];
        $this->float_format = [];
        $this->none_format = [];
        foreach ($field_names as $field) {
            $this->align[$field] = 'l'; // Default left alignment
            $this->max_width[$field] = strlen((string)$field);
            $this->int_format[$field] = '';
            $this->float_format[$field] = '';
            $this->none_format[$field] = null;
        }
    }

    /**
     * Add a row to the table
     * @param array $row Array of row data
     * @throws Exception if row length is incorrect
     */
    public function addRow($row) {
        if (!empty($this->field_names) && count($row) != count($this->field_names)) {
            throw new Exception("Row has incorrect number of values, (actual) " . count($row) . "!=" . count($this->field_names) . " (expected)");
        }
        if (empty($this->field_names)) {
            $this->setFieldNames(array_map(function($i) { return "Field " . ($i + 1); }, range(0, count($row) - 1)));
        }
        $this->rows[] = $row;
        foreach ($row as $i => $cell) {
            $field = $this->field_names[$i];
            $this->max_width[$field] = max($this->max_width[$field], strlen((string)$this->formatValue($field, $cell)));
        }
    }

    /**
     * Set alignment for a column
     * @param string $field Column name
     * @param string $align 'l' (left), 'r' (right), 'c' (center)
     * @throws Exception if alignment or field is invalid
     */
    public function setAlign($field, $align) {
        if (!in_array($field, $this->field_names)) {
            throw new Exception("Invalid field name: $field");
        }
        if (!in_array($align, ['l', 'r', 'c'])) {
            throw new Exception("Alignment $align is invalid, use l, c, or r");
        }
        $this->align[$field] = $align;
    }

    /**
     * Set border visibility
     * @param bool $border True to show borders, false to hide
     */
    public function setBorder($border) {
        if (!is_bool($border)) {
            throw new Exception("Border must be true or false");
        }
        $this->border = $border;
    }

    /**
     * Set horizontal rule style
     * @param int $hrules HRuleStyle constant (FRAME, ALL, NONE, HEADER)
     */
    public function setHrules($hrules) {
        if (!in_array($hrules, [HRuleStyle::FRAME, HRuleStyle::ALL, HRuleStyle::NONE, HRuleStyle::HEADER])) {
            throw new Exception("Invalid hrules value. Must be HRuleStyle constant");
        }
        $this->hrules = $hrules;
    }

    /**
     * Set vertical rule style
     * @param int $vrules VRuleStyle constant (FRAME, ALL, NONE)
     */
    public function setVrules($vrules) {
        if (!in_array($vrules, [VRuleStyle::FRAME, VRuleStyle::ALL, VRuleStyle::NONE])) {
            throw new Exception("Invalid vrules value. Must be VRuleStyle constant");
        }
        $this->vrules = $vrules;
    }

    /**
     * Set padding width
     * @param int $padding_width Number of spaces for padding
     */
    public function setPaddingWidth($padding_width) {
        if (!is_int($padding_width) || $padding_width < 0) {
            throw new Exception("Padding width must be a non-negative integer");
        }
        $this->padding_width = $padding_width;
    }

    /**
     * Set left padding width
     * @param int|null $left_padding_width Number of spaces or null
     */
    public function setLeftPaddingWidth($left_padding_width) {
        if (!is_null($left_padding_width) && (!is_int($left_padding_width) || $left_padding_width < 0)) {
            throw new Exception("Left padding width must be a non-negative integer or null");
        }
        $this->left_padding_width = $left_padding_width;
    }

    /**
     * Set right padding width
     * @param int|null $right_padding_width Number of spaces or null
     */
    public function setRightPaddingWidth($right_padding_width) {
        if (!is_null($right_padding_width) && (!is_int($right_padding_width) || $right_padding_width < 0)) {
            throw new Exception("Right padding width must be a non-negative integer or null");
        }
        $this->right_padding_width = $right_padding_width;
    }

    /**
     * Set vertical rule character
     * @param string $vertical_char Single character for vertical lines
     */
    public function setVerticalChar($vertical_char) {
        if (strlen($vertical_char) !== 1) {
            throw new Exception("Vertical char must be a single character");
        }
        $this->vertical_char = $vertical_char;
    }

    /**
     * Set horizontal rule character
     * @param string $horizontal_char Single character for horizontal lines
     */
    public function setHorizontalChar($horizontal_char) {
        if (strlen($horizontal_char) !== 1) {
            throw new Exception("Horizontal char must be a single character");
        }
        $this->horizontal_char = $horizontal_char;
    }

    /**
     * Set junction character
     * @param string $junction_char Single character for junctions
     */
    public function setJunctionChar($junction_char) {
        if (strlen($junction_char) !== 1) {
            throw new Exception("Junction char must be a single character");
        }
        $this->junction_char = $junction_char;
    }

    /**
     * Set integer format for a column
     * @param string $field Column name
     * @param string $format Format string (e.g., "%d")
     */
    public function setIntFormat($field, $format) {
        if (!in_array($field, $this->field_names)) {
            throw new Exception("Invalid field name: $field");
        }
        if ($format !== '' && !preg_match('/^\d+$/', $format)) {
            throw new Exception("Invalid integer format: $format");
        }
        $this->int_format[$field] = $format;
    }

    /**
     * Set float format for a column
     * @param string $field Column name
     * @param string $format Format string (e.g., "%.2f")
     */
    public function setFloatFormat($field, $format) {
        if (!in_array($field, $this->field_names)) {
            throw new Exception("Invalid field name: $field");
        }
        if ($format !== '' && !preg_match('/^\d*\.\d+[f]?$/', $format)) {
            throw new Exception("Invalid float format: $format");
        }
        $this->float_format[$field] = $format;
    }

    /**
     * Set none format for a column
     * @param string $field Column name
     * @param string|null $format Replacement string for null values
     */
    public function setNoneFormat($field, $format) {
        if (!in_array($field, $this->field_names)) {
            throw new Exception("Invalid field name: $field");
        }
        if (!is_null($format) && !is_string($format)) {
            throw new Exception("None format must be a string or null");
        }
        $this->none_format[$field] = $format;
    }

    /**
     * Format a value based on its type and column settings
     * @param string $field Column name
     * @param mixed $value Value to format
     * @return string Formatted value
     */
    private function formatValue($field, $value) {
        if ($value === null && $this->none_format[$field] !== null) {
            return $this->none_format[$field];
        }
        if (is_int($value) && $this->int_format[$field]) {
            return sprintf("%{$this->int_format[$field]}d", $value);
        }
        if (is_float($value) && $this->float_format[$field]) {
            return sprintf("%{$this->float_format[$field]}f", $value);
        }
        return (string)$value;
    }

    /**
     * Justify text based on alignment and width
     * @param string $text Text to justify
     * @param int $width Target width
     * @param string $align Alignment ('l', 'r', 'c')
     * @return string Justified text
     */
    private function justify($text, $width, $align) {
        $excess = $width - strlen($text);
        if ($excess <= 0) {
            return $text;
        }
        if ($align === 'l') {
            return $text . str_repeat(' ', $excess);
        } elseif ($align === 'r') {
            return str_repeat(' ', $excess) . $text;
        } else {
            $left = (int)($excess / 2);
            $right = $excess - $left;
            return str_repeat(' ', $left) . $text . str_repeat(' ', $right);
        }
    }

    /**
     * Get padding widths
     * @return array [left_pad, right_pad]
     */
    private function getPaddingWidths() {
        $lpad = $this->left_padding_width ?? $this->padding_width;
        $rpad = $this->right_padding_width ?? $this->padding_width;
        return [$lpad, $rpad];
    }

    /**
     * Generate horizontal rule
     * @return string Horizontal rule
     */
    private function getHrule() {
        if (!$this->border) {
            return '';
        }
        $bits = ($this->vrules !== VRuleStyle::NONE) ? [$this->junction_char] : [''];
        foreach ($this->field_names as $field) {
            [$lpad, $rpad] = $this->getPaddingWidths();
            $bits[] = str_repeat($this->horizontal_char, $this->max_width[$field] + $lpad + $rpad);
            if ($this->vrules === VRuleStyle::ALL) {
                $bits[] = $this->junction_char;
            } else {
                $bits[] = '';
            }
        }
        if ($this->vrules !== VRuleStyle::NONE) {
            $bits[count($bits) - 1] = $this->junction_char;
        }
        return implode('', $bits);
    }

    /**
     * Generate the table as a string
     * @return string Formatted table
     */
    public function getString() {
        if (empty($this->field_names)) {
            return '';
        }

        $lines = [];
        $hrule = $this->getHrule();

        // Header
        if ($this->border && $this->hrules !== HRuleStyle::NONE) {
            $lines[] = $hrule;
        }
        $header_row = [];
        [$lpad, $rpad] = $this->getPaddingWidths();
        foreach ($this->field_names as $field) {
            $width = $this->max_width[$field];
            $header_row[] = str_repeat(' ', $lpad) . $this->justify($field, $width, $this->align[$field]) . str_repeat(' ', $rpad);
        }
        $lines[] = ($this->vrules !== VRuleStyle::NONE ? $this->vertical_char : '') . implode($this->vertical_char, $header_row) . ($this->vrules !== VRuleStyle::NONE ? $this->vertical_char : '');
        if ($this->border && in_array($this->hrules, [HRuleStyle::ALL, HRuleStyle::HEADER])) {
            $lines[] = $hrule;
        }

        // Rows
        foreach ($this->rows as $row) {
            $formatted_row = [];
            foreach ($this->field_names as $i => $field) {
                $value = $this->formatValue($field, $row[$i]);
                $width = $this->max_width[$field];
                $formatted_row[] = str_repeat(' ', $lpad) . $this->justify($value, $width, $this->align[$field]) . str_repeat(' ', $rpad);
            }
            $lines[] = ($this->vrules !== VRuleStyle::NONE ? $this->vertical_char : '') . implode($this->vertical_char, $formatted_row) . ($this->vrules !== VRuleStyle::NONE ? $this->vertical_char : '');
            if ($this->border && $this->hrules === HRuleStyle::ALL) {
                $lines[] = $hrule;
            }
        }

        if ($this->border && $this->hrules === HRuleStyle::FRAME && !empty($this->rows)) {
            $lines[] = $hrule;
        }

        return implode("\n", $lines);
    }

    /**
     * Return string representation of the table
     * @return string
     */
    public function __toString() {
        return $this->getString();
    }
}

?>
