# manner

Convert troff man pages to semantic HTML.

This is used for rendering pages on [ManKier](https://www.mankier.com/).

Macro conversion:

- `.TH` → `<title>, <h1>`: [title heading macros](https://www.mankier.com/7/groff_man#Description-Document_structure_macros) become HTML [title](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/title) elements and the highest level of HTML [section heading](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/Heading_Elements).
- `.SH, .SS` → `<section>, <h2>, <h3>`: [section heading macros](https://www.mankier.com/7/groff_man#Description-Document_structure_macros) become HTML [section](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/section) elements with HTML section headings.
- `.PP` → `<p>`: paragraph macros become HTML paragraphs.
- `.TP, .TQ` → `<dl>, <dt>, <dd>`: sequences of [tagged paragraph macros](https://www.mankier.com/7/groff_man#Description-Paragraphing_macros) become HTML [definition lists](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/dl).
- `.IP \(bu` → `<ul>`: indented paragraph macros used for lists become HTML [unordered lists](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/ul)
- `.TS, .TE` → `<table>`: tables handled by the [tbl](https://www.mankier.com/1/tbl) preprocessor for troff become HTML [tables](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/table).
- `.UR, .UE` → `<a href="">`: [hyperlink URI macros](https://www.mankier.com/7/groff_man#Description-Hyperlink_macros) become HTML URL [anchors](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/a).
- `.MT, .ME` → `<a href="">`: hyperlink email macros become HTML URL anchors with a `mailto:` scheme

## Example

See in the `examples` folder:

```
.TH EXAMPLE "1" "June 2024" "manner" "Example Man Page"

.SH NAME
example \- sample man page

.SH SYNOPSIS
.B example
[\fI\,OPTION\/\fR]...

.SH DESCRIPTION
.\" This is a comment
.PP
Not a real command or man page!
.TP
\fB\-a\fR, \fB\-\-all\fR
a sample option
.TP
\fB\-b\fR, \fB\-\-ball\fR
another sample option
.PP

.TS
tab(@);
l l.
T{
Column 1
T}@T{
Column 2
T}
_
T{
row1
T}@T{
This is some tabular date
T}
T{
this is the second row
T}@T{
translated to an HTML <table>
T}
.TE


.SH SEE ALSO
.IP \(bu
First thing to see:
.UR https://example.com/something
Some Project
.UE .
.IP \(bu
Second thing to see.

.SH AUTHORS
This was written by
.MT author1@example.com
Author 1
.ME
and
.MT author2@example.com
Author 2
.ME .
```

After running:

`./manner.php example.1 > example.html`

... the output is compact, but reformatting with [Prettier](https://prettier.io/) to show the structure of the output gives:

```html
<!doctype html>
<meta charset="utf-8" />
<meta
  name="man-page-info"
  data-extra1="June 2024"
  data-extra2="manner"
  data-extra3="Example Man Page"
/>
<title>EXAMPLE</title>
<body>
  <h1>EXAMPLE</h1>
  <section>
    <h2>NAME</h2>
    <p>example - sample man page</p>
  </section>
  <section>
    <h2>SYNOPSIS</h2>
    <p><strong>example</strong> [<em>OPTION</em>]...</p>
  </section>
  <section>
    <h2>DESCRIPTION</h2>
    <p>Not a real command or man page!</p>
    <dl>
      <dt><strong>-a</strong>, <strong>--all</strong></dt>
      <dd><p>a sample option</p></dd>
      <dt><strong>-b</strong>, <strong>--ball</strong></dt>
      <dd><p>another sample option</p></dd>
    </dl>
    <table>
      <tr class="border-bottom">
        <td>Column 1</td>
        <td>Column 2</td>
      </tr>
      <tr>
        <td>row1</td>
        <td>This is some tabular date</td>
      </tr>
      <tr>
        <td>this is the second row</td>
        <td>translated to an HTML &lt;table&gt;</td>
      </tr>
    </table>
  </section>
  <section>
    <h2>SEE ALSO</h2>
    <ul>
      <li class="p">
        First thing to see:
        <a href="https://example.com/something">Some Project</a>.
      </li>
      <li class="p">Second thing to see.</li>
    </ul>
  </section>
  <section>
    <h2>AUTHORS</h2>
    <p>
      This was written by <a href="mailto:author1@example.com">Author 1</a> and
      <a href="mailto:author2@example.com">Author 2</a>.
    </p>
  </section>
</body>
```

## Requirements

- PHP >= 8.4
- A few man pages contain `.EQ` macros, for these the `eqn` command is required.
- A few man pages contain `.PS` macros, for these the `pic2plot` and `inkscape` commands are required.
