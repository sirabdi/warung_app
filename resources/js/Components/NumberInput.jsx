import { forwardRef } from 'react';
import { formatNumber, parseNumber } from '../lib';

// Number input with thousand separators and a numeric keypad on phones.
const NumberInput = forwardRef(function NumberInput({ value, onChange, className = 'input', ...props }, ref) {
    return (
        <input
            ref={ref}
            type="text"
            inputMode="numeric"
            autoComplete="off"
            className={className}
            value={value === '' || value == null ? '' : formatNumber(value)}
            onChange={(e) => onChange(parseNumber(e.target.value))}
            {...props}
        />
    );
});

export default NumberInput;
