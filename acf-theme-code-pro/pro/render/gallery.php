<?php
// Gallery field

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

echo $this->indent . htmlspecialchars("<?php \$".$this->var_name."_images = " . $this->get_field_method . "( '".$this->name."' ); ?>"."\n");
echo $this->indent . htmlspecialchars("<?php if ( \$".$this->var_name."_images ) :  ?>")."\n";
echo $this->indent . htmlspecialchars("	<?php foreach ( \$".$this->var_name."_images as \$".$this->var_name."_image ): ?>")."\n";
echo $this->indent . htmlspecialchars("		<a href=\"<?php echo \$".$this->var_name."_image['url']; ?>\">")."\n";
echo $this->indent . htmlspecialchars("			<img src=\"<?php echo \$".$this->var_name."_image['sizes']['thumbnail']; ?>\" alt=\"<?php echo \$".$this->var_name."_image['alt']; ?>\" />")."\n";
echo $this->indent . htmlspecialchars("		</a>")."\n";
echo $this->indent . htmlspecialchars("	<p><?php echo \$".$this->var_name."_image['caption']; ?></p>")."\n";
echo $this->indent . htmlspecialchars("	<?php endforeach; ?>")."\n";
echo $this->indent . htmlspecialchars("<?php endif; ?>"."\n");
