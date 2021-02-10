# Next-gen image support for Perch CMS

## Installation
Simply copy the `nextgenimage` directory into the `perch/addons/fieldtypes/` directory in an existing Perch installation.

## Usage
This extends the default image field type, meaning that all existing perch images will output identically by default. To take advantage of webp compression, set the output attribute to `nextgen`.

`<perch:content type="nextgenimage" output="nextgen" id="mynextgenimage" label="My next gen image" width="400" class="some-class" html />`
Results in:
```html
<picture>
    <source type="image/webp" srcset="/perch/resources/some-image-w400.png.webp">
    <img class="some-class" src="/perch/resources/some-image-w400.png" alt="An alt tag">
</picture>
```

### Notes
- This field type includes an alt attribute field in the editor.
- SVGs are ignored (outputting as normal image output type `tag` format) when `output="nextgen"`
- Non-Perch tags added to the `perch:content` tag will be passed directly to the nested `img` tag when output type is `nextgen`

## To-do
1. Add an output type for the webp file path
2. Add JPEG2000 support
3. Consider setting default output attribute value to `nextgen`