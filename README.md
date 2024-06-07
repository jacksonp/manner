# manner

Convert troff man pages to semantic HTML.

This is used for rendering pages on [ManKier](https://www.mankier.com/).

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
  </section>
</body>
```

