import DOMPurify from "dompurify";
import { marked } from "marked";

/**
 * Convert assistant markdown to sanitized HTML for v-html (lists, bold, code, links).
 */
export function assistantMarkdownToHtml(markdown) {
    if (typeof markdown !== "string" || markdown.trim() === "") {
        return "";
    }
    const raw = marked.parse(markdown, { async: false, breaks: true });
    if (typeof raw !== "string") {
        return "";
    }
    return DOMPurify.sanitize(raw);
}
