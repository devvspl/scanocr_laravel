/**
 * WolfBooks Formula Engine
 * Safe expression parser for form calculations.
 * Supports: +, -, *, /, %, parentheses, SUM(), AVG(), MIN(), MAX(), ROUND(), IF()
 * References fields via {field_key} and table columns via {table_key.col_key}
 */
class FormulaEngine {
    constructor(options = {}) {
        this.precision = options.precision ?? 2;
        this.currency = options.currency ?? '₹';
        this.roundMode = options.roundMode ?? 'none'; // none, round, floor, ceil
    }

    /**
     * Evaluate a formula string with given field values
     * @param {string} formula - e.g. "{qty} * {rate}"
     * @param {object} values - { field_key: value, "table.col": [array of values] }
     * @returns {number|null}
     */
    evaluate(formula, values = {}) {
        if (!formula || typeof formula !== 'string') return null;

        try {
            // Replace field references with values
            let expr = this.resolveReferences(formula, values);
            // Parse and evaluate safely
            let result = this.parseExpression(expr);
            if (result === null || isNaN(result) || !isFinite(result)) return null;
            return this.applyPrecision(result);
        } catch (e) {
            console.warn('Formula evaluation error:', e.message, formula);
            return null;
        }
    }

    /**
     * Replace {field_key} and {table.col} references with actual values
     */
    resolveReferences(formula, values) {
        return formula.replace(/\{([^}]+)\}/g, (match, key) => {
            if (key.includes('.')) {
                // Table column reference — returns array for aggregate functions
                const val = values[key];
                if (Array.isArray(val)) {
                    return '[' + val.map(v => Number(v) || 0).join(',') + ']';
                }
                return '0';
            }
            const val = values[key];
            if (val === undefined || val === null || val === '') return '0';
            // Keep string values as quoted strings for comparison in IF()
            if (typeof val === 'string' && isNaN(val)) {
                return '"' + val.replace(/"/g, '') + '"';
            }
            return String(Number(val) || 0);
        });
    }

    /**
     * Safe expression parser (no eval)
     */
    parseExpression(expr) {
        // Tokenize
        const tokens = this.tokenize(expr);
        const result = this.parseExpr(tokens, { pos: 0 });
        return result;
    }

    tokenize(expr) {
        const tokens = [];
        let i = 0;
        expr = expr.trim();

        while (i < expr.length) {
            const ch = expr[i];

            // Skip whitespace
            if (/\s/.test(ch)) { i++; continue; }

            // String literal (quoted)
            if (ch === '"' || ch === "'") {
                const quote = ch;
                i++; // skip opening quote
                let str = '';
                while (i < expr.length && expr[i] !== quote) { str += expr[i]; i++; }
                i++; // skip closing quote
                tokens.push({ type: 'string', value: str });
                continue;
            }

            // Number
            if (/[0-9.]/.test(ch)) {
                let num = '';
                while (i < expr.length && /[0-9.]/.test(expr[i])) { num += expr[i]; i++; }
                tokens.push({ type: 'number', value: parseFloat(num) });
                continue;
            }

            // Negative number (unary minus)
            if (ch === '-' && (tokens.length === 0 || ['(', ',', '+', '-', '*', '/', '%', '<', '>', '=', '!'].includes(tokens[tokens.length - 1]?.value))) {
                i++;
                let num = '-';
                while (i < expr.length && /[0-9.]/.test(expr[i])) { num += expr[i]; i++; }
                if (num.length > 1) {
                    tokens.push({ type: 'number', value: parseFloat(num) });
                } else {
                    tokens.push({ type: 'op', value: '-' });
                }
                continue;
            }

            // Operators
            if ('+-*/%()'.includes(ch)) {
                tokens.push({ type: ch === '(' || ch === ')' ? 'paren' : 'op', value: ch });
                i++; continue;
            }

            // Comparison operators
            if (ch === '>' || ch === '<' || ch === '=' || ch === '!') {
                let op = ch; i++;
                if (i < expr.length && expr[i] === '=') { op += '='; i++; }
                tokens.push({ type: 'comp', value: op });
                continue;
            }

            // Comma
            if (ch === ',') { tokens.push({ type: 'comma', value: ',' }); i++; continue; }

            // Array literal [1,2,3]
            if (ch === '[') {
                let arr = '';
                i++; // skip [
                while (i < expr.length && expr[i] !== ']') { arr += expr[i]; i++; }
                i++; // skip ]
                const values = arr.split(',').map(v => parseFloat(v.trim()) || 0);
                tokens.push({ type: 'array', value: values });
                continue;
            }

            // Function name or keyword
            if (/[a-zA-Z_]/.test(ch)) {
                let name = '';
                while (i < expr.length && /[a-zA-Z_0-9]/.test(expr[i])) { name += expr[i]; i++; }
                tokens.push({ type: 'func', value: name.toUpperCase() });
                continue;
            }

            i++; // skip unknown
        }

        return tokens;
    }

    parseExpr(tokens, ctx) {
        let left = this.parseTerm(tokens, ctx);

        while (ctx.pos < tokens.length) {
            const tok = tokens[ctx.pos];
            if (tok && tok.type === 'op' && (tok.value === '+' || tok.value === '-')) {
                ctx.pos++;
                const right = this.parseTerm(tokens, ctx);
                left = tok.value === '+' ? left + right : left - right;
            } else if (tok && tok.type === 'comp') {
                const op = tok.value; ctx.pos++;
                const right = this.parseTerm(tokens, ctx);
                switch (op) {
                    case '>': left = left > right ? 1 : 0; break;
                    case '<': left = left < right ? 1 : 0; break;
                    case '>=': left = left >= right ? 1 : 0; break;
                    case '<=': left = left <= right ? 1 : 0; break;
                    case '==': case '=': left = left == right ? 1 : 0; break;
                    case '!=': left = left != right ? 1 : 0; break;
                }
            } else {
                break;
            }
        }
        return left;
    }

    parseTerm(tokens, ctx) {
        let left = this.parseFactor(tokens, ctx);

        while (ctx.pos < tokens.length) {
            const tok = tokens[ctx.pos];
            if (tok && tok.type === 'op' && (tok.value === '*' || tok.value === '/' || tok.value === '%')) {
                ctx.pos++;
                const right = this.parseFactor(tokens, ctx);
                if (tok.value === '*') left = left * right;
                else if (tok.value === '/') left = right !== 0 ? left / right : 0;
                else left = left % right;
            } else {
                break;
            }
        }
        return left;
    }

    parseFactor(tokens, ctx) {
        const tok = tokens[ctx.pos];
        if (!tok) return 0;

        // Number
        if (tok.type === 'number') {
            ctx.pos++;
            return tok.value;
        }

        // String literal (for comparisons)
        if (tok.type === 'string') {
            ctx.pos++;
            return tok.value;
        }

        // Array
        if (tok.type === 'array') {
            ctx.pos++;
            return tok.value; // return as array for aggregate functions
        }

        // Parenthesized expression
        if (tok.type === 'paren' && tok.value === '(') {
            ctx.pos++;
            const val = this.parseExpr(tokens, ctx);
            if (tokens[ctx.pos]?.value === ')') ctx.pos++;
            return val;
        }

        // Function call
        if (tok.type === 'func') {
            const funcName = tok.value;
            ctx.pos++;
            // Expect (
            if (tokens[ctx.pos]?.value === '(') {
                ctx.pos++;
                const args = this.parseArgs(tokens, ctx);
                if (tokens[ctx.pos]?.value === ')') ctx.pos++;
                return this.callFunction(funcName, args);
            }
            return 0;
        }

        ctx.pos++;
        return 0;
    }

    parseArgs(tokens, ctx) {
        const args = [];
        while (ctx.pos < tokens.length && tokens[ctx.pos]?.value !== ')') {
            args.push(this.parseExpr(tokens, ctx));
            if (tokens[ctx.pos]?.type === 'comma') ctx.pos++;
        }
        return args;
    }

    callFunction(name, args) {
        // Flatten arrays in args for aggregate functions
        const flatArgs = args.flatMap(a => Array.isArray(a) ? a : [a]);

        switch (name) {
            case 'SUM': return flatArgs.reduce((s, v) => s + (Number(v) || 0), 0);
            case 'AVG': return flatArgs.length > 0 ? flatArgs.reduce((s, v) => s + (Number(v) || 0), 0) / flatArgs.length : 0;
            case 'MIN': return flatArgs.length > 0 ? Math.min(...flatArgs) : 0;
            case 'MAX': return flatArgs.length > 0 ? Math.max(...flatArgs) : 0;
            case 'ROUND': return args.length >= 2 ? Number(Number(args[0]).toFixed(args[1])) : Math.round(args[0] || 0);
            case 'ABS': return Math.abs(args[0] || 0);
            case 'CEIL': return Math.ceil(args[0] || 0);
            case 'FLOOR': return Math.floor(args[0] || 0);
            case 'IF': return args[0] ? (args[1] ?? 0) : (args[2] ?? 0);
            default: return 0;
        }
    }

    applyPrecision(value) {
        if (this.roundMode === 'round') value = Math.round(value);
        else if (this.roundMode === 'floor') value = Math.floor(value);
        else if (this.roundMode === 'ceil') value = Math.ceil(value);
        return Number(value.toFixed(this.precision));
    }

    /**
     * Format a number with currency
     */
    format(value) {
        if (value === null || value === undefined) return '—';
        return this.currency + ' ' + Number(value).toLocaleString('en-IN', {
            minimumFractionDigits: this.precision,
            maximumFractionDigits: this.precision,
        });
    }

    /**
     * Extract field keys referenced in a formula
     * @returns {string[]} e.g. ['qty', 'rate', 'items.amount']
     */
    extractDependencies(formula) {
        if (!formula) return [];
        const matches = formula.match(/\{([^}]+)\}/g) || [];
        return matches.map(m => m.slice(1, -1));
    }
}

// Export for use
if (typeof window !== 'undefined') {
    window.FormulaEngine = FormulaEngine;
}
if (typeof module !== 'undefined') {
    module.exports = FormulaEngine;
}
